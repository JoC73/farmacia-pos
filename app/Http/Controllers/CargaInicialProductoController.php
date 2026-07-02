<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use DateTimeInterface;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class CargaInicialProductoController extends Controller
{
    public function index()
    {
        return view('inventarios.carga-inicial', [
            'sucursales' => $this->sucursales(),
            'previewRows' => collect(),
            'importErrors' => collect(),
            'selectedSucursales' => [],
            'previewToken' => null,
        ]);
    }

    public function download()
    {
        $filename = 'plantilla-carga-inicial-productos.xlsx';
        $path = storage_path('app/' . $filename);
        $writer = new XlsxWriter();

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([
            'codigo_barra',
            'nombre',
            'categoria',
            'laboratorio',
            'costo',
            'precio_venta',
            'stock_minimo',
            'fecha_vencimiento',
            'existencia_inicial',
            'descripcion',
        ]));
        $writer->addRow(Row::fromValues([
            '',
            'Acetaminofen 500mg',
            'Analgesicos',
            'Generico',
            0.50,
            1.25,
            10,
            '2027-12-31',
            100,
            'La existencia se aplicara a las sucursales seleccionadas.',
        ]));
        $writer->close();

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'sucursal_ids' => ['required', 'array', 'min:1'],
            'sucursal_ids.*' => ['required', 'integer', 'exists:sucursales,id'],
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:4096'],
        ]);

        $sucursalIds = $this->normalizeSucursalIds($data['sucursal_ids']);
        $this->authorizeSucursalesAccess($sucursalIds);

        try {
            [$previewRows, $importErrors] = $this->parseXlsx(
                $request->file('archivo')->getRealPath()
            );
        } catch (\Throwable $exception) {
            report($exception);

            $previewRows = collect();
            $importErrors = collect([
                'No se pudo leer el archivo Excel. Verifica que uses la plantilla oficial y que las fechas esten en formato valido.',
            ]);
        }

        $previewToken = null;

        if ($previewRows->isNotEmpty() && $importErrors->isEmpty()) {
            $previewRows = $this->assignMissingCodes($previewRows);
            $previewToken = (string) Str::uuid();

            session([
                "carga_inicial_productos.{$previewToken}" => [
                    'sucursal_ids' => $sucursalIds,
                    'rows' => $previewRows->values()->all(),
                ],
            ]);
        }

        return view('inventarios.carga-inicial', [
            'sucursales' => $this->sucursales(),
            'previewRows' => $previewRows,
            'importErrors' => $importErrors,
            'selectedSucursales' => $sucursalIds,
            'previewToken' => $previewToken,
        ]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'sucursal_ids' => ['required', 'array', 'min:1'],
            'sucursal_ids.*' => ['required', 'integer', 'exists:sucursales,id'],
            'preview_token' => ['required', 'string'],
        ]);

        $sucursalIds = $this->normalizeSucursalIds($data['sucursal_ids']);
        $this->authorizeSucursalesAccess($sucursalIds);

        $preview = session()->pull("carga_inicial_productos.{$data['preview_token']}");

        if (!$preview || $this->normalizeSucursalIds($preview['sucursal_ids'] ?? []) !== $sucursalIds) {
            return redirect()
                ->route('inventarios.carga-inicial')
                ->with('error', 'La vista previa expiro. Vuelve a validar el archivo antes de confirmar.');
        }

        $rows = collect($preview['rows']);

        if ($rows->isEmpty()) {
            return redirect()
                ->route('inventarios.carga-inicial')
                ->with('error', 'No hay productos para importar.');
        }

        $creados = 0;
        $actualizados = 0;
        $movimientos = 0;

        DB::transaction(function () use ($rows, $sucursalIds, &$creados, &$actualizados, &$movimientos) {
            foreach ($rows as $row) {
                $categoria = null;

                if ($row['categoria']) {
                    $categoria = $this->resolveCategory($row['categoria']);
                }

                $producto = $this->findExistingProduct($row['codigo_barra'], $row['nombre'], $row['laboratorio']);

                if ($producto) {
                    $producto->update([
                        'categoria_id' => $categoria?->id,
                        'nombre' => $row['nombre'],
                        'laboratorio' => $row['laboratorio'],
                        'costo' => $row['costo'],
                        'precio_venta' => $row['precio_venta'],
                        'stock_minimo' => $row['stock_minimo'],
                        'fecha_vencimiento' => $row['fecha_vencimiento'],
                        'descripcion' => $row['descripcion'],
                        'estado' => true,
                    ]);
                    $actualizados++;
                } else {
                    $producto = Producto::create([
                        'categoria_id' => $categoria?->id,
                        'codigo_barra' => $row['codigo_barra'],
                        'nombre' => $row['nombre'],
                        'laboratorio' => $row['laboratorio'],
                        'costo' => $row['costo'],
                        'precio_venta' => $row['precio_venta'],
                        'stock_minimo' => $row['stock_minimo'],
                        'fecha_vencimiento' => $row['fecha_vencimiento'],
                        'descripcion' => $row['descripcion'],
                        'estado' => true,
                    ]);
                    $creados++;
                }

                foreach ($sucursalIds as $sucursalId) {
                    $inventario = Inventario::firstOrCreate(
                        [
                            'producto_id' => $producto->id,
                            'sucursal_id' => $sucursalId,
                        ],
                        ['existencia' => 0]
                    );

                    $existenciaAnterior = (int) $inventario->existencia;
                    $existenciaNueva = (int) $row['existencia_inicial'];

                    $fechaAnterior = optional($inventario->fecha_vencimiento)->format('Y-m-d');
                    $fechaNueva = $row['fecha_vencimiento'];
                    $fechaCambio = $fechaAnterior !== $fechaNueva;

                    if ($existenciaNueva !== $existenciaAnterior || $fechaCambio) {
                        $inventario->update([
                            'existencia' => $existenciaNueva,
                            'fecha_vencimiento' => $fechaNueva,
                        ]);
                    }

                    if ($existenciaNueva !== $existenciaAnterior) {

                        MovimientoInventario::create([
                            'producto_id' => $producto->id,
                            'sucursal_id' => $sucursalId,
                            'user_id' => auth()->id(),
                            'tipo_movimiento' => $existenciaNueva >= $existenciaAnterior ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA',
                            'cantidad' => abs($existenciaNueva - $existenciaAnterior),
                            'existencia_anterior' => $existenciaAnterior,
                            'existencia_nueva' => $existenciaNueva,
                            'referencia' => 'Carga inicial masiva',
                            'observacion' => 'Carga inicial masiva de productos multi-sucursal',
                        ]);

                        $movimientos++;
                    }
                }
            }
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', "Carga inicial aplicada en " . count($sucursalIds) . " sucursal(es). Productos creados: {$creados}. Actualizados: {$actualizados}. Movimientos: {$movimientos}.");
    }

    private function parseXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $reader->open($path);

        $headers = null;
        $previewRows = collect();
        $errors = collect();
        $line = 0;
        $codesInFile = [];
        $identitiesInFile = [];
        $productLookup = $this->productLookup();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $values = $row->toArray();

                if ($headers === null) {
                    $headers = $this->normalizeHeaders($values);
                    $errors = $this->validateHeaders($headers);

                    if ($errors->isNotEmpty()) {
                        $reader->close();
                        return [$previewRows, $errors];
                    }

                    continue;
                }

                $row = array_combine($headers, array_slice(array_pad($values, count($headers), null), 0, count($headers)));

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                try {
                    $this->addPreviewRow($row, $line, $previewRows, $errors, $codesInFile, $identitiesInFile, $productLookup);
                } catch (InvalidArgumentException $exception) {
                    $errors->push("Linea {$line}: {$exception->getMessage()}");
                }
            }

            break;
        }

        $reader->close();

        if ($headers === null) {
            $errors->push('El archivo esta vacio o no tiene encabezados.');
        }

        if ($previewRows->isEmpty() && $errors->isEmpty()) {
            $errors->push('No se encontraron productos para importar.');
        }

        return [$previewRows, $errors];
    }

    private function addPreviewRow(array $row, int $line, $previewRows, $errors, array &$codesInFile, array &$identitiesInFile, array $productLookup): void
    {
        $nombre = $this->textValue($row['nombre'] ?? '');
        $codigo = $this->textValue($row['codigo_barra'] ?? '');
        $existencia = $this->integerTextValue($row['existencia_inicial'] ?? '');

        if ($nombre === '') {
            $errors->push("Linea {$line}: el nombre es obligatorio.");
            return;
        }

        if (Str::length($nombre) > 200) {
            $errors->push("Linea {$line}: el nombre no puede superar 200 caracteres.");
            return;
        }

        if ($existencia === '' || !ctype_digit($existencia)) {
            $errors->push("Linea {$line}: existencia_inicial debe ser un numero entero mayor o igual a 0.");
            return;
        }

        $costo = $this->numberValue($row['costo'] ?? 0);
        $precio = $this->numberValue($row['precio_venta'] ?? 0);
        $stockMinimo = $this->integerTextValue($row['stock_minimo'] ?? '5');

        if ($costo < 0 || $precio < 0) {
            $errors->push("Linea {$line}: costo y precio_venta no pueden ser negativos.");
            return;
        }

        if ($stockMinimo === '' || !ctype_digit($stockMinimo)) {
            $errors->push("Linea {$line}: stock_minimo debe ser un numero entero mayor o igual a 0.");
            return;
        }

        if ($codigo !== '') {
            if (isset($codesInFile[$codigo])) {
                $errors->push("Linea {$line}: codigo_barra duplicado dentro del archivo.");
                return;
            }

            $codesInFile[$codigo] = true;
        } else {
            $identity = $this->productIdentityKey($nombre, $this->textValue($row['laboratorio'] ?? ''));

            if (isset($identitiesInFile[$identity])) {
                $errors->push("Linea {$line}: producto duplicado dentro del archivo sin codigo_barra.");
                return;
            }

            $identitiesInFile[$identity] = true;
        }

        $categoria = $this->textValue($row['categoria'] ?? '');
        $laboratorio = $this->textValue($row['laboratorio'] ?? '');
        $descripcion = $this->textValue($row['descripcion'] ?? '');
        $fechaVencimiento = $this->dateValue($row['fecha_vencimiento'] ?? null);

        if (Str::length($categoria) > 120) {
            $errors->push("Linea {$line}: la categoria no puede superar 120 caracteres.");
            return;
        }

        if (Str::length($laboratorio) > 150) {
            $errors->push("Linea {$line}: el laboratorio no puede superar 150 caracteres.");
            return;
        }

        $productoExistente = $this->findExistingProductFromLookup($productLookup, $codigo, $nombre, $laboratorio);

        $previewRows->push([
            'codigo_barra' => $productoExistente?->codigo_barra ?? $codigo,
            'codigo_generado' => $codigo === '' && ! $productoExistente,
            'accion' => $productoExistente ? 'Actualizar' : 'Crear',
            'nombre' => $nombre,
            'categoria' => $categoria,
            'laboratorio' => $laboratorio,
            'costo' => $costo,
            'precio_venta' => $precio,
            'stock_minimo' => (int) $stockMinimo,
            'fecha_vencimiento' => $fechaVencimiento,
            'existencia_inicial' => (int) $existencia,
            'descripcion' => $descripcion,
        ]);
    }

    private function assignMissingCodes($rows)
    {
        $nextNumber = $this->nextInternalCodeNumber();
        $usedCodes = Producto::pluck('codigo_barra')->flip()->all();

        foreach ($rows as $row) {
            if ($row['codigo_barra'] !== '') {
                $usedCodes[$row['codigo_barra']] = true;
            }
        }

        return $rows->map(function ($row) use (&$nextNumber, &$usedCodes) {
            if ($row['codigo_barra'] !== '') {
                return $row;
            }

            do {
                $code = str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
                $nextNumber++;
            } while (isset($usedCodes[$code]));

            $usedCodes[$code] = true;
            $row['codigo_barra'] = $code;

            return $row;
        });
    }

    private function productLookup(): array
    {
        $productos = Producto::orderByDesc('estado')
            ->orderBy('id')
            ->get();

        return [
            'by_code' => $productos
                ->filter(fn ($producto) => trim((string) $producto->codigo_barra) !== '')
                ->unique('codigo_barra')
                ->keyBy('codigo_barra'),
            'by_identity' => $productos
                ->unique(fn ($producto) => $this->productIdentityKey($producto->nombre, $producto->laboratorio))
                ->keyBy(fn ($producto) => $this->productIdentityKey($producto->nombre, $producto->laboratorio)),
        ];
    }

    private function findExistingProductFromLookup(array $lookup, ?string $codigo, string $nombre, ?string $laboratorio): ?Producto
    {
        $codigo = trim((string) $codigo);

        if ($codigo !== '' && isset($lookup['by_code'][$codigo])) {
            return $lookup['by_code'][$codigo];
        }

        $identity = $this->productIdentityKey($nombre, $laboratorio);

        return $lookup['by_identity'][$identity] ?? null;
    }

    private function findExistingProduct(?string $codigo, string $nombre, ?string $laboratorio): ?Producto
    {
        $codigo = trim((string) $codigo);

        if ($codigo !== '') {
            $producto = Producto::where('codigo_barra', $codigo)->first();

            if ($producto) {
                return $producto;
            }
        }

        return Producto::whereRaw('LOWER(nombre) = ?', [Str::lower(trim($nombre))])
            ->whereRaw("LOWER(COALESCE(laboratorio, '')) = ?", [Str::lower(trim((string) $laboratorio))])
            ->orderByDesc('estado')
            ->orderBy('id')
            ->first();
    }

    private function resolveCategory(?string $name): ?Categoria
    {
        $displayName = Categoria::displayName($name);

        if ($displayName === '') {
            return null;
        }

        $normalizedName = Categoria::normalizeName($displayName);

        $categoria = Categoria::where('nombre_normalizado', $normalizedName)
            ->orderByDesc('estado')
            ->orderBy('id')
            ->first();

        if ($categoria) {
            if (!$categoria->estado) {
                $categoria->update(['estado' => true]);
            }

            return $categoria;
        }

        return Categoria::create([
            'nombre' => $displayName,
            'nombre_normalizado' => $normalizedName,
            'estado' => true,
        ]);
    }

    private function productIdentityKey(string $nombre, ?string $laboratorio): string
    {
        return Str::lower(trim($nombre)) . '|' . Str::lower(trim((string) $laboratorio));
    }

    private function nextInternalCodeNumber(): int
    {
        $max = Producto::whereRaw("codigo_barra ~ '^[0-9]+$'")
            ->selectRaw('MAX(CAST(codigo_barra AS INTEGER)) as max_code')
            ->value('max_code');

        return ((int) $max) + 1;
    }

    private function normalizeHeaders(array $headers): array
    {
        return collect($headers)
            ->map(fn ($header) => Str::snake(Str::lower(trim((string) $header))))
            ->all();
    }

    private function validateHeaders(array $headers)
    {
        $errors = collect();
        $required = ['nombre', 'costo', 'precio_venta', 'existencia_inicial'];

        foreach ($required as $header) {
            if (!in_array($header, $headers, true)) {
                $errors->push("Falta la columna requerida: {$header}.");
            }
        }

        return $errors;
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)
            ->filter(fn ($value) => $this->textValue($value) !== '')
            ->isEmpty();
    }

    private function numberValue($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = $this->textValue($value);

        if ($value === '') {
            return 0;
        }

        $value = str_replace(['Q', 'q', ' '], '', $value);
        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('costo y precio_venta deben ser numeros validos.');
        }

        return (float) $value;
    }

    private function dateValue($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_int($value) || is_float($value)) {
            if ($value <= 0) {
                return null;
            }

            return now()
                ->setDate(1899, 12, 30)
                ->startOfDay()
                ->addDays((int) $value)
                ->format('Y-m-d');
        }

        $value = $this->textValue($value);

        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new InvalidArgumentException('fecha_vencimiento debe tener formato YYYY-MM-DD o DD/MM/YYYY.');
    }

    private function textValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return Str::of((string) $value)
            ->squish()
            ->toString();
    }

    private function integerTextValue($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return $this->textValue($value);
    }

    private function sucursales()
    {
        $sucursalId = auth()->user()->visibleSucursalId();

        return Sucursal::where('estado', true)
            ->when($sucursalId, fn ($query) => $query->whereKey($sucursalId))
            ->orderBy('nombre')
            ->get();
    }

    private function normalizeSucursalIds(array $sucursalIds): array
    {
        return collect($sucursalIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function authorizeSucursalesAccess(array $sucursalIds): void
    {
        foreach ($sucursalIds as $sucursalId) {
            abort_unless(auth()->user()->canAccessSucursal($sucursalId), 403);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = [
        'nombre',
        'nombre_normalizado',
        'descripcion',
        'estado',
    ];

    protected static function booted(): void
    {
        static::saving(function (Categoria $categoria) {
            $categoria->nombre = static::cleanName((string) $categoria->nombre);
            $categoria->nombre_normalizado = static::normalizeName((string) $categoria->nombre);
        });
    }

    public static function cleanName(?string $name): string
    {
        return Str::of((string) $name)
            ->squish()
            ->toString();
    }

    public static function displayName(?string $name): string
    {
        return Str::upper(static::cleanName($name));
    }

    public static function normalizeName(?string $name): string
    {
        $asciiName = Str::ascii(static::cleanName($name));
        $normalized = Str::lower($asciiName);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';

        return Str::of($normalized)
            ->squish()
            ->toString();
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}

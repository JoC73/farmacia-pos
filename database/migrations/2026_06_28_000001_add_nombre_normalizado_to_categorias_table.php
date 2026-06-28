<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('categorias', 'nombre_normalizado')) {
                $table->string('nombre_normalizado', 120)->nullable()->index();
            }
        });

        DB::table('categorias')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->chunkById(200, function ($categorias) {
                foreach ($categorias as $categoria) {
                    DB::table('categorias')
                        ->where('id', $categoria->id)
                        ->update([
                            'nombre_normalizado' => $this->normalizeCategoryName($categoria->nombre),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            if (Schema::hasColumn('categorias', 'nombre_normalizado')) {
                $table->dropIndex(['nombre_normalizado']);
                $table->dropColumn('nombre_normalizado');
            }
        });
    }

    private function normalizeCategoryName(?string $name): string
    {
        $clean = Str::of((string) $name)->squish()->toString();
        $asciiName = Str::ascii($clean);
        $normalized = Str::lower($asciiName);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';

        return Str::of($normalized)->squish()->toString();
    }
};

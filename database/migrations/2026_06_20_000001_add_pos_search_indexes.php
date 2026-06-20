<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->index(['sucursal_id', 'existencia'], 'inventarios_sucursal_existencia_index');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->index(['estado', 'nombre'], 'productos_estado_nombre_index');
            $table->index(['estado', 'laboratorio'], 'productos_estado_laboratorio_index');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_estado_laboratorio_index');
            $table->dropIndex('productos_estado_nombre_index');
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropIndex('inventarios_sucursal_existencia_index');
        });
    }
};

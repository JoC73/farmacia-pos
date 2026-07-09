<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->string('categoria_local', 120)->nullable()->after('nombre_local');
            $table->string('laboratorio_local', 150)->nullable()->after('categoria_local');
            $table->decimal('costo_local', 10, 2)->nullable()->after('laboratorio_local');
            $table->decimal('precio_venta_local', 10, 2)->nullable()->after('costo_local');
            $table->unsignedInteger('stock_minimo_local')->nullable()->after('precio_venta_local');
            $table->text('descripcion_local')->nullable()->after('stock_minimo_local');
        });
    }

    public function down(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropColumn([
                'categoria_local',
                'laboratorio_local',
                'costo_local',
                'precio_venta_local',
                'stock_minimo_local',
                'descripcion_local',
            ]);
        });
    }
};

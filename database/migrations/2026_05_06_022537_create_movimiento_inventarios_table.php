<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movimiento_inventarios', function (Blueprint $table) {

            $table->id();

            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->cascadeOnDelete();

            $table->foreignId('sucursal_id')
                  ->constrained('sucursales')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->enum('tipo_movimiento', [

                'COMPRA',
                'VENTA',
                'AJUSTE_ENTRADA',
                'AJUSTE_SALIDA',
                'TRASLADO_ENTRADA',
                'TRASLADO_SALIDA',
                'DEVOLUCION_CLIENTE',
                'DEVOLUCION_PROVEEDOR',
                'VENCIMIENTO'

            ]);

            $table->integer('cantidad');

            $table->integer('existencia_anterior');

            $table->integer('existencia_nueva');

            $table->string('referencia')->nullable();

            $table->text('observacion')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventarios');
    }
};
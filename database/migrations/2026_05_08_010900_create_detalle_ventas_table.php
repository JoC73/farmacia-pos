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
        Schema::create('detalle_ventas', function (Blueprint $table) {

            $table->id();

            // VENTA
            $table->foreignId('venta_id')
                  ->constrained('ventas')
                  ->cascadeOnDelete();

            // PRODUCTO
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->cascadeOnDelete();

            // CANTIDAD
            $table->integer('cantidad');

            // PRECIO
            $table->decimal('precio_unitario', 12, 2);

            // SUBTOTAL
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
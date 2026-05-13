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
        Schema::create('ventas', function (Blueprint $table) {

            $table->id();

            // SUCURSAL
            $table->foreignId('sucursal_id')
                  ->constrained('sucursales')
                  ->cascadeOnDelete();

            // USUARIO/VENDEDOR
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // NO FACTURA
            $table->string('numero_factura')->unique();

            // CLIENTE SIMPLE (por ahora)
            $table->string('cliente')->nullable();

            // TOTALES
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->decimal('descuento', 12, 2)->default(0);

            $table->decimal('total', 12, 2)->default(0);

            // ESTADO
            $table->enum('estado', [
                'PENDIENTE',
                'FINALIZADA',
                'ANULADA'
            ])->default('FINALIZADA');

            // OBSERVACIÓN
            $table->text('observacion')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
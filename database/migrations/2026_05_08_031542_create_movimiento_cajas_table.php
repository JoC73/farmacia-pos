<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_cajas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('caja_id')
                  ->constrained('cajas')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->enum('tipo', [

                'INGRESO',
                'EGRESO',
                'VENTA',
                'COMPRA',
                'APERTURA',
                'CIERRE',
                'AJUSTE',
                'TRANSFERENCIA_JEFE'

            ]);

            $table->decimal('monto', 12, 2);

            $table->string('referencia')
                  ->nullable();

            $table->text('descripcion')
                  ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_cajas');
    }
};

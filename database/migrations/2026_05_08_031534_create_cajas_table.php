<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sucursal_id')
                  ->constrained('sucursales')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->decimal('monto_apertura', 12, 2)
                  ->default(0);

            $table->decimal('monto_cierre', 12, 2)
                  ->nullable();

            $table->decimal('total_sistema', 12, 2)
                  ->default(0);

            $table->decimal('diferencia', 12, 2)
                  ->default(0);

            $table->timestamp('fecha_apertura');

            $table->timestamp('fecha_cierre')
                  ->nullable();

            $table->enum('estado', [
                'ABIERTA',
                'CERRADA'
            ])->default('ABIERTA');

            $table->text('observacion')
                  ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corte_mensual_cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('periodo_year');
            $table->unsignedTinyInteger('periodo_month');
            $table->decimal('saldo_inicial', 12, 2)->default(0);
            $table->decimal('ventas', 12, 2)->default(0);
            $table->decimal('egresos', 12, 2)->default(0);
            $table->decimal('transferencias_previas', 12, 2)->default(0);
            $table->decimal('disponible_antes', 12, 2)->default(0);
            $table->decimal('monto_transferido', 12, 2)->default(0);
            $table->decimal('saldo_restante', 12, 2)->default(0);
            $table->timestamp('fecha_corte');
            $table->string('referencia')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['sucursal_id', 'periodo_year', 'periodo_month'], 'corte_mensual_sucursal_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corte_mensual_cajas');
    }
};

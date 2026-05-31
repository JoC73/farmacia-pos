<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->timestamp('fecha_movimiento')
                ->nullable()
                ->after('monto');
        });
    }

    public function down(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropColumn('fecha_movimiento');
        });
    }
};

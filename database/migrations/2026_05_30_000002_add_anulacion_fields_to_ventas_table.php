<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('anulada_por')
                ->nullable()
                ->after('observacion')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_anulacion')
                ->nullable()
                ->after('anulada_por');

            $table->text('motivo_anulacion')
                ->nullable()
                ->after('fecha_anulacion');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulada_por');
            $table->dropColumn([
                'fecha_anulacion',
                'motivo_anulacion',
            ]);
        });
    }
};

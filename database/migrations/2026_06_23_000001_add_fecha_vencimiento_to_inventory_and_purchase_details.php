<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('existencia');
        });

        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->dropColumn('fecha_vencimiento');
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropColumn('fecha_vencimiento');
        });
    }
};

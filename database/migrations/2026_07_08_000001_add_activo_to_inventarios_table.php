<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('nombre_local');
            $table->index(['sucursal_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropIndex(['sucursal_id', 'activo']);
            $table->dropColumn('activo');
        });
    }
};

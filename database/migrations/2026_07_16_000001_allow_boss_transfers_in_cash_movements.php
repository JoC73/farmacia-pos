<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('movimiento_cajas', 'fecha_movimiento')) {
            Schema::table('movimiento_cajas', function (Blueprint $table) {
                $table->timestamp('fecha_movimiento')
                    ->nullable()
                    ->after('monto');
            });
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement($this->dropTipoCheckConstraintsSql());

        DB::statement("
            ALTER TABLE movimiento_cajas
            ADD CONSTRAINT movimiento_cajas_tipo_check
            CHECK (tipo IN (
                'INGRESO',
                'EGRESO',
                'VENTA',
                'COMPRA',
                'APERTURA',
                'CIERRE',
                'AJUSTE',
                'TRANSFERENCIA_JEFE'
            ))
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement($this->dropTipoCheckConstraintsSql());

        DB::statement("
            ALTER TABLE movimiento_cajas
            ADD CONSTRAINT movimiento_cajas_tipo_check
            CHECK (tipo IN (
                'INGRESO',
                'EGRESO',
                'VENTA',
                'COMPRA',
                'APERTURA',
                'CIERRE',
                'AJUSTE'
            ))
        ");
    }

    private function dropTipoCheckConstraintsSql(): string
    {
        return <<<'SQL'
DO $$
DECLARE
    constraint_record record;
BEGIN
    FOR constraint_record IN
        SELECT conname
        FROM pg_constraint
        WHERE conrelid = 'movimiento_cajas'::regclass
          AND contype = 'c'
          AND pg_get_constraintdef(oid) ILIKE '%tipo%'
    LOOP
        EXECUTE format('ALTER TABLE movimiento_cajas DROP CONSTRAINT IF EXISTS %I', constraint_record.conname);
    END LOOP;
END $$;
SQL;
    }
};

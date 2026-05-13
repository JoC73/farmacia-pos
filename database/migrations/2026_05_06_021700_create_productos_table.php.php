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
    Schema::create('productos', function (Blueprint $table) {

        $table->id();

        $table->foreignId('categoria_id')
              ->nullable()
              ->constrained('categorias')
              ->nullOnDelete();

        $table->string('codigo_barra', 120)->unique();

        $table->string('nombre', 200);

        $table->string('laboratorio', 150)->nullable();

        $table->decimal('costo', 10, 2)->default(0);

        $table->decimal('precio_venta', 10, 2)->default(0);

        $table->integer('stock_minimo')->default(5);

        $table->date('fecha_vencimiento')->nullable();

        $table->text('descripcion')->nullable();

        $table->boolean('estado')->default(true);

        $table->timestamps();

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

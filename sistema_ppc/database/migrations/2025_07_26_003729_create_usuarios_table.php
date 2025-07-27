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
        Schema::create('usuarios', function (Blueprint $table) {

            $table->integer('id', true);
            $table->string('nome');
            $table->string('email')->unique('email');
            $table->string('senha');
            $table->enum('tipo', ['submissor', 'avaliador', 'decisor']);
            $table->dateTime('criado_em')->nullable()->useCurrent();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};

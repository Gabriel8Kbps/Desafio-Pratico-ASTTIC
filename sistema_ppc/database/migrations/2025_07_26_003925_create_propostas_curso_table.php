<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propostas_curso', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->integer('carga_horaria_total');
            $table->integer('quantidade_semestres');
            $table->text('justificativa');
            $table->text('impacto_social');
            $table->text('comentario_avaliador')->nullable();
            $table->text('comentario_decisor')->nullable();

            $table->unsignedBigInteger('id_autor')->nullable();
            $table->unsignedBigInteger('id_avaliador')->nullable();
            $table->unsignedBigInteger('id_decisor_final')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propostas_curso');
    }
};
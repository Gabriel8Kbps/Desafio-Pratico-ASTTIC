<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_proposta_curso', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_proposta');
            $table->enum('status', ['submetida', 'em_avaliacao', 'ajustes_requeridos', 'em_aprovacao', 'aprovada', 'rejeitada']);
            $table->dateTime('data_status')->useCurrent();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_proposta_curso');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_proposta_curso', function (Blueprint $table) {
            $table->foreign('id_proposta')
                  ->references('id')->on('propostas_curso')
                  ->onUpdate('restrict')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('status_proposta_curso', function (Blueprint $table) {
            $table->dropForeign(['id_proposta']);
        });
    }
};
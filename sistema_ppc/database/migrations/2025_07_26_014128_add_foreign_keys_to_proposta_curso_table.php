<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas_curso', function (Blueprint $table) {
            $table->foreign('id_autor')
                  ->references('id')->on('usuarios')
                  ->onDelete('set null');

            $table->foreign('id_avaliador')
                  ->references('id')->on('usuarios')
                  ->onDelete('set null');

            $table->foreign('id_decisor_final')
                  ->references('id')->on('usuarios')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('propostas_curso', function (Blueprint $table) {
            $table->dropForeign(['id_autor']);
            $table->dropForeign(['id_avaliador']);
            $table->dropForeign(['id_decisor_final']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id(); 
            $table->string('nome');
            $table->string('email')->unique(); 
            $table->string('senha');
            $table->enum('tipo', ['submissor', 'avaliador', 'decisor']);
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
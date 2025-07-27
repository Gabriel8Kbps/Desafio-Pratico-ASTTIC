<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'tipo'
    ];

    public function propostasCriadas()
    {
        return $this->hasMany(PropostaCurso::class, 'id_autor');
    }

    public function propostasAvaliadas()
    {
        return $this->hasMany(PropostaCurso::class, 'id_avaliador');
    }

    public function propostasDecididas()
    {
        return $this->hasMany(PropostaCurso::class, 'id_decisor_final');
    }
}
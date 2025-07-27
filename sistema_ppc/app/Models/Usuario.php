<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'tipo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    
    protected $hidden = [
        'senha',
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

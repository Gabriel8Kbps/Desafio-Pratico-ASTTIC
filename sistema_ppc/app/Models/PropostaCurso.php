<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use illuminate\Database\Eloquent\Factories\HasFactory;

class PropostaCurso extends Model
{
    use HasFactory;
    protected $table = 'propostas_curso';

    protected $fillable = [
        'nome',
        'carga_horaria_total',
        'quantidade_semestres',
        'justificativa',
        'impacto_social',
        'comentario_avaliador',
        'comentario_decisor',
        'id_autor',
        'id_avaliador',
        'id_decisor_final'
    ];
     public function disciplinas()
    {
        return $this->hasMany(Disciplina::class, 'id_curso');
    }

    public function historicoStatus()
    {
        return $this->hasMany(StatusProposta::class, 'id_proposta');
    }

    public function autor()
    {
        return $this->belongsTo(Usuario::class, 'id_autor');
    }

    public function avaliador()
    {
        return $this->belongsTo(Usuario::class, 'id_avaliador');
    }

    public function decisorFinal()
    {
        return $this->belongsTo(Usuario::class, 'id_decisor_final');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusProposta extends Model
{
    use HasFactory;

    protected $table = 'status_proposta_curso';

    protected $fillable = [
        'id_proposta',
        'status',
        'data_status',
        'observacao'
    ];

    public function proposta()
    {
        return $this->belongsTo(PropostaCurso::class, 'id_proposta');
    }
}
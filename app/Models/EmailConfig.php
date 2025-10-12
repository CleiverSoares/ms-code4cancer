<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailConfig extends Model
{
    use HasFactory;

    protected $table = 'email_config';

    protected $fillable = [
        'email',
        'nome',
        'ativo',
        'tipo_alerta'
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean'
        ];
    }

    /**
     * Obter emails ativos para alertas prioritários
     */
    public static function obterEmailsPrioritarios(): array
    {
        return self::where('ativo', true)
            ->where('tipo_alerta', 'prioritario')
            ->pluck('email', 'nome')
            ->toArray();
    }
}
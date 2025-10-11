<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsuarioModel extends Model
{
    use HasFactory;

    protected $table = 'users';

    /**
     * Campos que podem ser preenchidos em massa
     */
    protected $fillable = [
        'firebase_uid',
        'email',
        'email_verified',
        'nome',
        'foto_url',
        'provider',
        'provider_id',
        'firebase_created_at',
        'ultimo_login_at',
        'ultimo_refresh_at',
        'ativo',
        'preferencias',
        'observacoes'
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'firebase_created_at' => 'datetime',
            'ultimo_login_at' => 'datetime',
            'ultimo_refresh_at' => 'datetime',
            'ativo' => 'boolean',
            'preferencias' => 'array'
        ];
    }

    /**
     * Verificar se o usuário está ativo
     */
    public function estaAtivo(): bool
    {
        return $this->ativo;
    }

    /**
     * Relacionamento com conversas do chat (futuro)
     */
    public function conversasChat(): HasMany
    {
        return $this->hasMany(ConversaChatModel::class, 'usuario_id');
    }
}

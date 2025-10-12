<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NoticiaModel extends Model
{
    protected $table = 'noticias';
    
    protected $fillable = [
        'titulo',
        'resumo',
        'url',
        'url_imagem',
        'alt_imagem',
        'legenda_imagem',
        'fonte',
        'data_publicacao',
        'categoria',
        'ativa'
    ];

    protected $casts = [
        'data_publicacao' => 'datetime',
        'ativa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope para buscar notícias ativas
     */
    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }

    /**
     * Scope para buscar por categoria
     */
    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope para buscar notícias recentes
     */
    public function scopeRecentes($query, int $dias = 7)
    {
        return $query->where('data_publicacao', '>=', Carbon::now()->subDays($dias));
    }

    /**
     * Scope para ordenar por data de publicação
     */
    public function scopeOrdenarPorData($query, string $direcao = 'desc')
    {
        return $query->orderBy('data_publicacao', $direcao);
    }
}

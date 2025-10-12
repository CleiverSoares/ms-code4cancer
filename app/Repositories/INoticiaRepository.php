<?php

namespace App\Repositories;

use App\Models\NoticiaModel;
use Illuminate\Database\Eloquent\Collection;

interface INoticiaRepository
{
    /**
     * Buscar todas as notícias ativas
     */
    public function buscarTodasAtivas(): Collection;

    /**
     * Buscar notícias por categoria
     */
    public function buscarPorCategoria(string $categoria): Collection;

    /**
     * Buscar notícias recentes
     */
    public function buscarRecentes(int $dias = 7): Collection;

    /**
     * Buscar notícias com paginação
     */
    public function buscarComPaginacao(int $porPagina = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Criar nova notícia
     */
    public function criar(array $dados): NoticiaModel;

    /**
     * Verificar se notícia já existe pela URL
     */
    public function existePorUrl(string $url): bool;

    /**
     * Buscar notícias por período
     */
    public function buscarPorPeriodo(\DateTime $dataInicio, \DateTime $dataFim): Collection;

    /**
     * Desativar notícias antigas
     */
    public function desativarAntigas(int $dias): int;
}

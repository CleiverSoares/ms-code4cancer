<?php

namespace App\Repositories;

use App\Models\NoticiaModel;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class NoticiaRepository implements INoticiaRepository
{
    public function buscarTodasAtivas(): Collection
    {
        return NoticiaModel::ativas()
            ->ordenarPorData()
            ->get();
    }

    public function buscarPorCategoria(string $categoria): Collection
    {
        return NoticiaModel::ativas()
            ->porCategoria($categoria)
            ->ordenarPorData()
            ->get();
    }

    public function buscarRecentes(int $dias = 7): Collection
    {
        return NoticiaModel::ativas()
            ->recentes($dias)
            ->ordenarPorData()
            ->get();
    }

    public function buscarComPaginacao(int $porPagina = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return NoticiaModel::ativas()
            ->ordenarPorData()
            ->paginate($porPagina);
    }

    public function criar(array $dados): NoticiaModel
    {
        return NoticiaModel::create($dados);
    }

    public function existePorUrl(string $url): bool
    {
        return NoticiaModel::where('url', $url)->exists();
    }

    public function buscarPorPeriodo(\DateTime $dataInicio, \DateTime $dataFim): Collection
    {
        return NoticiaModel::ativas()
            ->whereBetween('data_publicacao', [$dataInicio, $dataFim])
            ->ordenarPorData()
            ->get();
    }

    public function desativarAntigas(int $dias): int
    {
        $dataLimite = Carbon::now()->subDays($dias);
        
        return NoticiaModel::where('data_publicacao', '<', $dataLimite)
            ->where('ativa', true)
            ->update(['ativa' => false]);
    }
}

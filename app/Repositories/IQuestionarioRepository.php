<?php

namespace App\Repositories;

use App\Models\QuestionarioModel;
use Illuminate\Database\Eloquent\Collection;

interface IQuestionarioRepository
{
    /**
     * Buscar questionário por ID do usuário
     */
    public function buscarPorUsuario(int $usuarioId): ?QuestionarioModel;

    /**
     * Salvar ou atualizar questionário
     */
    public function salvar(array $dados): QuestionarioModel;

    /**
     * Atualizar questionário existente
     */
    public function atualizar(int $usuarioId, array $dados): bool;

    /**
     * Verificar se usuário já possui questionário
     */
    public function usuarioPossuiQuestionario(int $usuarioId): bool;

    /**
     * Buscar questionários por critérios de risco
     */
    public function buscarPorFatoresRisco(array $criterios): Collection;

    /**
     * Buscar questionários com sinais de alerta
     */
    public function buscarComSinaisAlerta(): Collection;

    /**
     * Buscar questionários elegíveis para rastreamento específico
     */
    public function buscarElegiveisParaRastreamento(string $tipoRastreamento): Collection;

    /**
     * Estatísticas gerais dos questionários
     */
    public function obterEstatisticas(): array;

    /**
     * Deletar questionário
     */
    public function deletar(int $usuarioId): bool;
}

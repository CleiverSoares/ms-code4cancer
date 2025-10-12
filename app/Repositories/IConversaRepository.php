<?php

namespace App\Repositories;

use App\Models\ConversaModel;

interface IConversaRepository
{
    /**
     * Buscar conversa por ID
     */
    public function buscarPorId(int $id): ?ConversaModel;

    /**
     * Buscar conversas de um usuário
     */
    public function buscarPorUsuario(int $usuarioId, int $limite = 20): array;

    /**
     * Buscar conversa ativa de um usuário
     */
    public function buscarConversaAtiva(int $usuarioId): ?ConversaModel;

    /**
     * Criar nova conversa
     */
    public function criarConversa(int $usuarioId, array $dados = []): ConversaModel;

    /**
     * Atualizar conversa
     */
    public function atualizarConversa(int $id, array $dados): bool;

    /**
     * Finalizar conversa
     */
    public function finalizarConversa(int $id, string $resumo, string $titulo = null): bool;

    /**
     * Buscar conversas recentes de um usuário
     */
    public function buscarConversasRecentes(int $usuarioId, int $dias = 30): array;

    /**
     * Buscar conversas por status
     */
    public function buscarPorStatus(int $usuarioId, string $status): array;

    /**
     * Deletar conversa
     */
    public function deletarConversa(int $id): bool;
}

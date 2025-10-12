<?php

namespace App\Repositories;

use App\Models\ConversaModel;
use Illuminate\Support\Facades\DB;

class ConversaRepository implements IConversaRepository
{
    /**
     * Buscar conversa por ID
     */
    public function buscarPorId(int $id): ?ConversaModel
    {
        return ConversaModel::find($id);
    }

    /**
     * Buscar conversas de um usuário
     */
    public function buscarPorUsuario(int $usuarioId, int $limite = 20): array
    {
        return ConversaModel::doUsuario($usuarioId)
            ->orderBy('iniciada_em', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();
    }

    /**
     * Buscar conversa ativa de um usuário
     */
    public function buscarConversaAtiva(int $usuarioId): ?ConversaModel
    {
        return ConversaModel::doUsuario($usuarioId)
            ->ativas()
            ->orderBy('iniciada_em', 'desc')
            ->first();
    }

    /**
     * Criar nova conversa
     */
    public function criarConversa(int $usuarioId, array $dados = []): ConversaModel
    {
        $dadosConversa = array_merge([
            'usuario_id' => $usuarioId,
            'status' => 'ativa',
            'total_mensagens' => 0,
            'total_tokens_usados' => 0,
            'iniciada_em' => now(),
            'ultima_mensagem_em' => now()
        ], $dados);

        return ConversaModel::create($dadosConversa);
    }

    /**
     * Atualizar conversa
     */
    public function atualizarConversa(int $id, array $dados): bool
    {
        $conversa = ConversaModel::find($id);
        
        if (!$conversa) {
            return false;
        }

        return $conversa->update($dados);
    }

    /**
     * Finalizar conversa
     */
    public function finalizarConversa(int $id, string $resumo, string $titulo = null): bool
    {
        $conversa = ConversaModel::find($id);
        
        if (!$conversa) {
            return false;
        }

        $conversa->finalizarComResumo($resumo, $titulo);
        
        return true;
    }

    /**
     * Buscar conversas recentes de um usuário
     */
    public function buscarConversasRecentes(int $usuarioId, int $dias = 30): array
    {
        return ConversaModel::doUsuario($usuarioId)
            ->recentes($dias)
            ->orderBy('iniciada_em', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Buscar conversas por status
     */
    public function buscarPorStatus(int $usuarioId, string $status): array
    {
        return ConversaModel::doUsuario($usuarioId)
            ->where('status', $status)
            ->orderBy('iniciada_em', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Deletar conversa
     */
    public function deletarConversa(int $id): bool
    {
        $conversa = ConversaModel::find($id);
        
        if (!$conversa) {
            return false;
        }

        return $conversa->delete();
    }

    /**
     * Obter estatísticas de conversas de um usuário
     */
    public function obterEstatisticasUsuario(int $usuarioId): array
    {
        $stats = ConversaModel::doUsuario($usuarioId)
            ->selectRaw('
                COUNT(*) as total_conversas,
                SUM(total_mensagens) as total_mensagens,
                SUM(total_tokens_usados) as total_tokens,
                AVG(total_mensagens) as media_mensagens_por_conversa,
                COUNT(CASE WHEN status = "ativa" THEN 1 END) as conversas_ativas,
                COUNT(CASE WHEN status = "finalizada" THEN 1 END) as conversas_finalizadas
            ')
            ->first();

        return [
            'total_conversas' => $stats->total_conversas ?? 0,
            'total_mensagens' => $stats->total_mensagens ?? 0,
            'total_tokens_usados' => $stats->total_tokens ?? 0,
            'media_mensagens_por_conversa' => round($stats->media_mensagens_por_conversa ?? 0, 2),
            'conversas_ativas' => $stats->conversas_ativas ?? 0,
            'conversas_finalizadas' => $stats->conversas_finalizadas ?? 0
        ];
    }
}

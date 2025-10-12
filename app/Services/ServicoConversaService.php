<?php

namespace App\Services;

use App\Repositories\IConversaRepository;
use App\Models\ConversaModel;
use Illuminate\Support\Facades\Log;

class ServicoConversaService
{
    public function __construct(
        private IConversaRepository $conversaRepository,
        private ServicoOpenAIService $servicoOpenAI
    ) {}

    /**
     * Iniciar nova conversa para um usuário
     */
    public function iniciarConversa(int $usuarioId, string $primeiraMensagem = null): ConversaModel
    {
        Log::info('=== INICIANDO NOVA CONVERSA ===');
        Log::info('Usuário ID: ' . $usuarioId);

        // Verificar se já existe conversa ativa
        $conversaAtiva = $this->conversaRepository->buscarConversaAtiva($usuarioId);
        
        if ($conversaAtiva) {
            Log::info('Conversa ativa encontrada: ' . $conversaAtiva->id);
            return $conversaAtiva;
        }

        // Criar nova conversa
        $conversa = $this->conversaRepository->criarConversa($usuarioId);
        
        Log::info('Nova conversa criada: ' . $conversa->id);

        // Se há primeira mensagem, adicionar ao histórico
        if ($primeiraMensagem) {
            $this->adicionarMensagem($conversa->id, $primeiraMensagem, 'Conversa iniciada');
        }

        return $conversa;
    }

    /**
     * Adicionar mensagem à conversa
     */
    public function adicionarMensagem(int $conversaId, string $mensagemUsuario, string $respostaSofia, array $metadados = []): bool
    {
        Log::info('=== ADICIONANDO MENSAGEM À CONVERSA ===');
        Log::info('Conversa ID: ' . $conversaId);
        Log::info('Mensagem: ' . substr($mensagemUsuario, 0, 100) . '...');

        $conversa = $this->conversaRepository->buscarPorId($conversaId);
        
        if (!$conversa) {
            Log::warning('Conversa não encontrada: ' . $conversaId);
            return false;
        }

        // Adicionar mensagem ao histórico
        $conversa->adicionarMensagem($mensagemUsuario, $respostaSofia, $metadados);

        // Atualizar contadores de tokens se disponível
        if (isset($metadados['tokens_usados'])) {
            $this->conversaRepository->atualizarConversa($conversaId, [
                'total_tokens_usados' => $conversa->total_tokens_usados + $metadados['tokens_usados']
            ]);
        }

        Log::info('Mensagem adicionada com sucesso');
        return true;
    }

    /**
     * Gerar resumo da conversa usando IA
     */
    public function gerarResumoConversa(int $conversaId): string
    {
        Log::info('=== GERANDO RESUMO DA CONVERSA ===');
        Log::info('Conversa ID: ' . $conversaId);

        $conversa = $this->conversaRepository->buscarPorId($conversaId);
        
        if (!$conversa) {
            Log::warning('Conversa não encontrada: ' . $conversaId);
            return 'Conversa não encontrada.';
        }

        $historico = $conversa->historico_mensagens ?? [];
        
        if (empty($historico)) {
            Log::warning('Histórico vazio para conversa: ' . $conversaId);
            return 'Nenhuma mensagem encontrada nesta conversa.';
        }

        // Preparar contexto para a IA
        $contextoConversa = $this->prepararContextoParaResumo($historico);
        
        $promptResumo = "Você é a SOFIA, assistente médica especializada em oncologia. 

Analise a seguinte conversa e gere um resumo médico profissional e estruturado:

CONVERSA:
{$contextoConversa}

INSTRUÇÕES PARA O RESUMO:
1. Identifique o motivo principal da consulta
2. Liste os sintomas ou preocupações mencionados
3. Registre as orientações médicas fornecidas
4. Destaque recomendações importantes
5. Mantenha linguagem técnica mas acessível
6. Seja objetivo e preciso
7. Use formato estruturado com tópicos

FORMATO DO RESUMO:
**Motivo da Consulta:** [motivo principal]
**Sintomas/Preocupações:** [lista de sintomas]
**Orientações Fornecidas:** [orientações dadas]
**Recomendações:** [recomendações importantes]
**Próximos Passos:** [ações sugeridas]

IMPORTANTE: Este resumo é para fins de acompanhamento médico e deve ser sempre validado por um profissional de saúde.";

        try {
            $resposta = $this->servicoOpenAI->processarPergunta($promptResumo);
            $resumo = $resposta['resposta'] ?? 'Erro ao gerar resumo.';
            
            Log::info('Resumo gerado com sucesso');
            Log::debug('Resumo: ' . substr($resumo, 0, 200) . '...');
            
            return $resumo;
            
        } catch (\Exception $e) {
            Log::error('Erro ao gerar resumo: ' . $e->getMessage());
            return 'Erro ao gerar resumo da conversa. Tente novamente.';
        }
    }

    /**
     * Finalizar conversa com resumo
     */
    public function finalizarConversa(int $conversaId, string $tituloPersonalizado = null): array
    {
        Log::info('=== FINALIZANDO CONVERSA ===');
        Log::info('Conversa ID: ' . $conversaId);

        $conversa = $this->conversaRepository->buscarPorId($conversaId);
        
        if (!$conversa) {
            return [
                'sucesso' => false,
                'erro' => 'Conversa não encontrada'
            ];
        }

        // Gerar resumo usando IA
        $resumo = $this->gerarResumoConversa($conversaId);
        
        // Finalizar conversa
        $sucesso = $this->conversaRepository->finalizarConversa($conversaId, $resumo, $tituloPersonalizado);
        
        if ($sucesso) {
            Log::info('Conversa finalizada com sucesso');
            
            return [
                'sucesso' => true,
                'conversa_id' => $conversaId,
                'resumo' => $resumo,
                'titulo' => $tituloPersonalizado ?? $conversa->titulo,
                'estatisticas' => $conversa->obterEstatisticas()
            ];
        }

        return [
            'sucesso' => false,
            'erro' => 'Erro ao finalizar conversa'
        ];
    }

    /**
     * Buscar conversas de um usuário
     */
    public function buscarConversasUsuario(int $usuarioId, int $limite = 20): array
    {
        Log::info('=== BUSCANDO CONVERSAS DO USUÁRIO ===');
        Log::info('Usuário ID: ' . $usuarioId);

        $conversas = $this->conversaRepository->buscarPorUsuario($usuarioId, $limite);
        
        Log::info('Conversas encontradas: ' . count($conversas));
        
        return $conversas;
    }

    /**
     * Buscar conversa específica
     */
    public function buscarConversa(int $conversaId, int $usuarioId): ?array
    {
        Log::info('=== BUSCANDO CONVERSA ESPECÍFICA ===');
        Log::info('Conversa ID: ' . $conversaId);
        Log::info('Usuário ID: ' . $usuarioId);

        $conversa = $this->conversaRepository->buscarPorId($conversaId);
        
        if (!$conversa || $conversa->usuario_id !== $usuarioId) {
            Log::warning('Conversa não encontrada ou não pertence ao usuário');
            return null;
        }

        return [
            'id' => $conversa->id,
            'titulo' => $conversa->titulo,
            'resumo' => $conversa->resumo,
            'historico_mensagens' => $conversa->historico_mensagens,
            'metadados' => $conversa->metadados,
            'status' => $conversa->status,
            'estatisticas' => $conversa->obterEstatisticas(),
            'iniciada_em' => $conversa->iniciada_em,
            'finalizada_em' => $conversa->finalizada_em
        ];
    }

    /**
     * Preparar contexto da conversa para resumo
     */
    private function prepararContextoParaResumo(array $historico): string
    {
        $contexto = '';
        
        foreach ($historico as $index => $mensagem) {
            $numero = $index + 1;
            $contexto .= "\n--- Mensagem {$numero} ---\n";
            $contexto .= "Usuário: " . $mensagem['usuario'] . "\n";
            $contexto .= "SOFIA: " . $mensagem['sofia'] . "\n";
        }
        
        return $contexto;
    }

    /**
     * Obter estatísticas de conversas do usuário
     */
    public function obterEstatisticasUsuario(int $usuarioId): array
    {
        return $this->conversaRepository->obterEstatisticasUsuario($usuarioId);
    }
}

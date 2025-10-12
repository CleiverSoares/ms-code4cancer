<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServicoPenAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private string $assistantId = 'asst_jvtZfT9xli7uNyJvYM1RWhzY';
    private array $threadsAtivos = [];

    public function __construct()
    {
        $this->apiKey = config('openai.api_key', env('OPENAI_API_KEY'));
    }

    /**
     * Inicia uma nova conversa com o assistente Pen AI
     */
    public function iniciarConversa(): array
    {
        Log::info('=== INICIANDO CONVERSA COM PEN AI ===');
        
        try {
            // Criar um novo thread
            $threadId = $this->criarThread();
            
            // Obter primeira pergunta do assistente
            $primeiraPergunta = $this->obterPrimeiraPergunta($threadId);
            
            Log::info('Conversa iniciada com sucesso. Thread ID: ' . $threadId);
            
            return [
                'sucesso' => true,
                'thread_id' => $threadId,
                'pergunta_atual' => $primeiraPergunta,
                'status' => 'conversa_iniciada',
                'timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao iniciar conversa com Pen AI: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro ao iniciar conversa',
                'detalhes' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Envia resposta do usuário e obtém próxima pergunta
     */
    public function enviarResposta(string $threadId, string $resposta): array
    {
        Log::info('=== ENVIANDO RESPOSTA PARA PEN AI ===');
        Log::info('Thread ID: ' . $threadId);
        Log::info('Resposta: ' . substr($resposta, 0, 200) . '...');
        
        try {
            // Enviar mensagem do usuário
            $this->enviarMensagemUsuario($threadId, $resposta);
            
            // Executar o assistente
            $this->executarAssistente($threadId);
            
            // Obter resposta do assistente
            $proximaPergunta = $this->obterRespostaAssistente($threadId);
            
            Log::info('Resposta processada com sucesso');
            
            return [
                'sucesso' => true,
                'thread_id' => $threadId,
                'resposta_enviada' => $resposta,
                'proxima_pergunta' => $proximaPergunta,
                'status' => 'resposta_processada',
                'timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao enviar resposta: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro ao processar resposta',
                'detalhes' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Obtém o histórico da conversa
     */
    public function obterHistoricoConversa(string $threadId): array
    {
        Log::info('=== OBTENDO HISTÓRICO DA CONVERSA ===');
        Log::info('Thread ID: ' . $threadId);
        
        try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        ])->withOptions([
            'verify' => false, // Desabilitar verificação SSL em desenvolvimento
        ])->get($this->baseUrl . "/threads/{$threadId}/messages");
            
            if ($response->failed()) {
                throw new \Exception('Erro ao obter histórico: ' . $response->body());
            }
            
            $data = $response->json();
            $historico = [];
            
            foreach ($data['data'] as $mensagem) {
                $historico[] = [
                    'role' => $mensagem['role'],
                    'content' => $mensagem['content'][0]['text']['value'] ?? '',
                    'timestamp' => date('Y-m-d H:i:s', $mensagem['created_at'])
                ];
            }
            
            return [
                'sucesso' => true,
                'thread_id' => $threadId,
                'historico' => array_reverse($historico), // Mais recente primeiro
                'total_mensagens' => count($historico),
                'timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao obter histórico: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro ao obter histórico',
                'detalhes' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Finaliza uma conversa
     */
    public function finalizarConversa(string $threadId): array
    {
        Log::info('=== FINALIZANDO CONVERSA ===');
        Log::info('Thread ID: ' . $threadId);
        
        try {
            // Marcar thread como inativo
            unset($this->threadsAtivos[$threadId]);
            
            Log::info('Conversa finalizada com sucesso');
            
            return [
                'sucesso' => true,
                'thread_id' => $threadId,
                'status' => 'conversa_finalizada',
                'timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao finalizar conversa: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro ao finalizar conversa',
                'detalhes' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Testa conexão com o assistente Pen AI
     */
    public function testarConexao(): array
    {
        Log::info('=== TESTANDO CONEXÃO COM PEN AI ===');
        
        try {
            // Verificar se o assistente existe
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            ])->withOptions([
                'verify' => false, // Desabilitar verificação SSL em desenvolvimento
            ])->get($this->baseUrl . "/assistants/{$this->assistantId}");
            
            if ($response->failed()) {
                throw new \Exception('Assistente não encontrado: ' . $response->body());
            }
            
            $assistant = $response->json();
            
            return [
                'sucesso' => true,
                'assistant_id' => $this->assistantId,
                'assistant_name' => $assistant['name'] ?? 'Pen AI Assistant',
                'status' => 'conexão_estabelecida',
                'timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro ao conectar com Pen AI',
                'detalhes' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Cria um novo thread para conversa
     */
    private function criarThread(): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        ])->withOptions([
            'verify' => false, // Desabilitar verificação SSL em desenvolvimento
        ])->post($this->baseUrl . '/threads');
        
        if ($response->failed()) {
            throw new \Exception('Erro ao criar thread: ' . $response->body());
        }
        
        $data = $response->json();
        $threadId = $data['id'];
        
        // Armazenar thread como ativo
        $this->threadsAtivos[$threadId] = true;
        
        return $threadId;
    }

    /**
     * Obtém a primeira pergunta do assistente
     */
    private function obterPrimeiraPergunta(string $threadId): string
    {
        // Executar o assistente para obter primeira pergunta
        $this->executarAssistente($threadId);
        
        // Obter resposta
        return $this->obterRespostaAssistente($threadId);
    }

    /**
     * Envia mensagem do usuário para o thread
     */
    private function enviarMensagemUsuario(string $threadId, string $mensagem): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        ])->withOptions([
            'verify' => false, // Desabilitar verificação SSL em desenvolvimento
        ])->post($this->baseUrl . "/threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $mensagem
        ]);
        
        if ($response->failed()) {
            throw new \Exception('Erro ao enviar mensagem: ' . $response->body());
        }
    }

    /**
     * Executa o assistente no thread
     */
    private function executarAssistente(string $threadId): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        ])->withOptions([
            'verify' => false, // Desabilitar verificação SSL em desenvolvimento
        ])->post($this->baseUrl . "/threads/{$threadId}/runs", [
            'assistant_id' => $this->assistantId
        ]);
        
        if ($response->failed()) {
            throw new \Exception('Erro ao executar assistente: ' . $response->body());
        }
        
        $data = $response->json();
        $runId = $data['id'];
        
        // Aguardar conclusão da execução
        $this->aguardarExecucao($threadId, $runId);
    }

    /**
     * Aguarda a conclusão da execução do assistente
     */
    private function aguardarExecucao(string $threadId, string $runId): void
    {
        $maxTentativas = 30; // 30 segundos máximo
        $tentativa = 0;
        
        do {
            sleep(1);
            $tentativa++;
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2'
            ])->withOptions([
                'verify' => false, // Desabilitar verificação SSL em desenvolvimento
            ])->get($this->baseUrl . "/threads/{$threadId}/runs/{$runId}");
            
            if ($response->failed()) {
                throw new \Exception('Erro ao verificar execução: ' . $response->body());
            }
            
            $data = $response->json();
            $status = $data['status'];
            
            if ($status === 'completed') {
                return;
            }
            
            if ($status === 'failed') {
                throw new \Exception('Execução falhou: ' . ($data['last_error']['message'] ?? 'Erro desconhecido'));
            }
            
        } while ($tentativa < $maxTentativas);
        
        throw new \Exception('Timeout na execução do assistente');
    }

    /**
     * Obtém a resposta mais recente do assistente
     */
    private function obterRespostaAssistente(string $threadId): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        ])->withOptions([
            'verify' => false, // Desabilitar verificação SSL em desenvolvimento
        ])->get($this->baseUrl . "/threads/{$threadId}/messages");
        
        if ($response->failed()) {
            throw new \Exception('Erro ao obter resposta: ' . $response->body());
        }
        
        $data = $response->json();
        
        // Buscar a mensagem mais recente do assistente
        foreach ($data['data'] as $mensagem) {
            if ($mensagem['role'] === 'assistant') {
                return $mensagem['content'][0]['text']['value'] ?? 'Resposta não disponível';
            }
        }
        
        return 'Nenhuma resposta encontrada';
    }
}

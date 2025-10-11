<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServicoOpenAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private string $modelo = 'gpt-3.5-turbo';

    public function __construct()
    {
        $this->apiKey = config('openai.api_key', env('OPENAI_API_KEY'));
    }

    /**
     * Processa uma pergunta e retorna resposta da IA
     */
    public function processarPergunta(string $pergunta): array
    {
        try {
            $resposta = $this->enviarRequisicaoGPT($pergunta);
            
            return [
                'sucesso' => true,
                'pergunta' => $pergunta,
                'resposta' => $resposta,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar pergunta GPT: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro interno do servidor',
                'pergunta' => $pergunta,
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Analisa respostas de questionário de paciente com câncer
     */
    public function analisarQuestionario(array $respostas): array
    {
        $prompt = $this->criarPromptAnalise($respostas);
        
        try {
            $analise = $this->enviarRequisicaoGPT($prompt);
            
            return [
                'sucesso' => true,
                'respostas' => $respostas,
                'analise' => $analise,
                'insights' => $this->extrairInsights($analise),
                'alertas' => $this->detectarAlertas($analise),
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao analisar questionário: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro ao processar análise',
                'respostas' => $respostas,
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Envia requisição para API do OpenAI
     */
    private function enviarRequisicaoGPT(string $prompt): string
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Chave da API OpenAI não configurada');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/chat/completions', [
            'model' => $this->modelo,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um assistente médico especializado em oncologia e cuidados paliativos. Responda sempre em português brasileiro.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7
        ]);

        if ($response->failed()) {
            throw new \Exception('Erro na API OpenAI: ' . $response->body());
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? 'Resposta não disponível';
    }

    /**
     * Cria prompt específico para análise de questionário
     */
    private function criarPromptAnalise(array $respostas): string
    {
        $respostasTexto = json_encode($respostas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return "Analise as seguintes respostas de um questionário de qualidade de vida de um paciente com câncer:

{$respostasTexto}

Por favor, forneça:
1. Uma análise geral da qualidade de vida do paciente
2. Principais preocupações identificadas
3. Recomendações para melhorar o bem-estar
4. Sinais de alerta que requerem atenção médica
5. Sugestões de cuidados paliativos se necessário

Responda de forma clara e objetiva, focando no bem-estar do paciente.";
    }

    /**
     * Extrai insights principais da análise
     */
    private function extrairInsights(string $analise): array
    {
        // Lógica simples para extrair insights
        $insights = [];
        
        if (strpos($analise, 'dor') !== false || strpos($analise, 'pain') !== false) {
            $insights[] = 'Gerenciamento de dor necessário';
        }
        
        if (strpos($analise, 'ansiedade') !== false || strpos($analise, 'depressão') !== false) {
            $insights[] = 'Suporte psicológico recomendado';
        }
        
        if (strpos($analise, 'fadiga') !== false || strpos($analise, 'cansaço') !== false) {
            $insights[] = 'Avaliação de fadiga necessária';
        }
        
        return $insights;
    }

    /**
     * Detecta alertas críticos na análise
     */
    private function detectarAlertas(string $analise): array
    {
        $alertas = [];
        
        $palavrasCriticas = [
            'urgente', 'emergência', 'crítico', 'grave', 'imediato',
            'suicídio', 'autoagressão', 'dor severa', 'deterioração'
        ];
        
        foreach ($palavrasCriticas as $palavra) {
            if (stripos($analise, $palavra) !== false) {
                $alertas[] = "Alerta detectado: {$palavra}";
            }
        }
        
        return $alertas;
    }

    /**
     * Configura modelo da IA
     */
    public function definirModelo(string $modelo): self
    {
        $this->modelo = $modelo;
        return $this;
    }

    /**
     * Testa conexão com OpenAI
     */
    public function testarConexao(): array
    {
        try {
            $resposta = $this->enviarRequisicaoGPT('Responda apenas "Conexão OK"');
            
            return [
                'sucesso' => true,
                'status' => 'Conexão estabelecida com sucesso',
                'modelo' => $this->modelo,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'sucesso' => false,
                'erro' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }
}

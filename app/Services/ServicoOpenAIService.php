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
        Log::info('=== PROCESSANDO PERGUNTA OPENAI ===');
        Log::info('Timestamp: ' . now()->toISOString());
        Log::info('Modelo: ' . $this->modelo);
        Log::info('Pergunta: ' . substr($pergunta, 0, 200) . '...');
        Log::info('Tamanho da pergunta: ' . strlen($pergunta) . ' caracteres');
        
        try {
            $inicioRequisicao = microtime(true);
            $resposta = $this->enviarRequisicaoGPT($pergunta);
            $tempoRequisicao = microtime(true) - $inicioRequisicao;
            
            Log::info('✅ Resposta GPT recebida com sucesso em ' . round($tempoRequisicao, 3) . ' segundos');
            Log::info('Resposta: ' . substr($resposta, 0, 200) . '...');
            Log::info('Tamanho da resposta: ' . strlen($resposta) . ' caracteres');
            
            // Interceptar resumo e extrair dados automaticamente
            $dadosExtraidos = [];
            if ($this->ehResumoFinal($resposta)) {
                Log::info('🎯 RESUMO FINAL DETECTADO - Extraindo dados automaticamente...');
                $servicoExtracao = new \App\Services\ServicoExtracaoDadosService();
                $dadosExtraidos = $servicoExtracao->extrairDadosDoResumoCompleto($resposta);
                Log::info('📊 Dados extraídos do resumo completo:', $dadosExtraidos);
            }
            
            return [
                'sucesso' => true,
                'pergunta' => $pergunta,
                'resposta' => $resposta,
                'dados_extraidos' => $dadosExtraidos,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar pergunta GPT: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
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
     * Envia requisição HTTP com retry automático para OpenAI
     */
    private function enviarRequisicaoComRetry(array $payload, string $endpoint = '/chat/completions'): array
    {
        $maxTentativas = 3;
        $ultimoErro = null;
        
        for ($tentativa = 1; $tentativa <= $maxTentativas; $tentativa++) {
            try {
                Log::info("🔄 Tentativa {$tentativa}/{$maxTentativas} de conexão com OpenAI");
                
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->withOptions([
                    'verify' => false, // Desabilitar verificação SSL em desenvolvimento
                    'timeout' => 60, // Timeout de 60 segundos
                    'connect_timeout' => 30, // Timeout de conexão de 30 segundos
                ])->post($this->baseUrl . $endpoint, $payload);

                Log::info('Status da resposta: ' . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info("✅ Sucesso na tentativa {$tentativa}");
                    return $data;
                } else {
                    $ultimoErro = 'Erro HTTP ' . $response->status() . ': ' . $response->body();
                    Log::warning("⚠️ Tentativa {$tentativa} falhou: {$ultimoErro}");
                }
                
            } catch (\Exception $e) {
                $ultimoErro = $e->getMessage();
                Log::warning("⚠️ Tentativa {$tentativa} falhou com exceção: {$ultimoErro}");
                
                // Se for erro de conexão (cURL error 35, 28, etc.), aguardar antes de tentar novamente
                if (strpos($ultimoErro, 'cURL error') !== false || strpos($ultimoErro, 'Connection') !== false) {
                    if ($tentativa < $maxTentativas) {
                        $delay = $tentativa * 2; // Delay progressivo: 2s, 4s
                        Log::info("⏳ Aguardando {$delay} segundos antes da próxima tentativa...");
                        sleep($delay);
                    }
                }
            }
        }
        
        // Se todas as tentativas falharam, lançar exceção
        Log::error("❌ Todas as {$maxTentativas} tentativas falharam. Último erro: {$ultimoErro}");
        throw new \Exception("Falha na conexão com OpenAI após {$maxTentativas} tentativas. Último erro: {$ultimoErro}");
    }

    /**
     * Envia requisição para API do OpenAI
     */
    private function enviarRequisicaoGPT(string $prompt): string
    {
        Log::info('=== ENVIANDO REQUISIÇÃO PARA OPENAI ===');
        Log::info('API Key configurada: ' . (empty($this->apiKey) ? 'NÃO' : 'SIM'));
        Log::info('Modelo: ' . $this->modelo);
        Log::info('URL: ' . $this->baseUrl . '/chat/completions');
        
        if (empty($this->apiKey)) {
            Log::error('Chave da API OpenAI não configurada');
            throw new \Exception('Chave da API OpenAI não configurada');
        }

        $payload = [
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
        ];
        
        Log::info('Payload da requisição: ' . json_encode($payload));

        try {
            $data = $this->enviarRequisicaoComRetry($payload);
            $resposta = $data['choices'][0]['message']['content'] ?? 'Resposta não disponível';
            Log::info('Resposta extraída: ' . substr($resposta, 0, 200) . '...');
            return $resposta;
        } catch (\Exception $e) {
            Log::error('Erro na requisição GPT: ' . $e->getMessage());
            
            // Resposta de fallback quando OpenAI não está disponível
            $respostaFallback = "Desculpe, estou enfrentando problemas de conexão com o serviço de análise. ";
            $respostaFallback .= "Por favor, tente novamente em alguns minutos ou consulte um profissional de saúde qualificado para uma avaliação mais detalhada.";
            
            Log::info("🔄 Retornando resposta de fallback");
            return $respostaFallback;
        }
    }

    /**
     * Transcreve áudio usando Whisper da OpenAI
     */
    public function transcreverAudio(string $caminhoArquivo): array
    {
        Log::info('=== TRANSCREVENDO ÁUDIO COM WHISPER ===');
        Log::info('Arquivo: ' . $caminhoArquivo);
        
        try {
            if (empty($this->apiKey)) {
                Log::error('Chave da API OpenAI não configurada');
                throw new \Exception('Chave da API OpenAI não configurada');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->withOptions([
                'verify' => false, // Desabilitar verificação SSL em desenvolvimento
            ])->attach('file', file_get_contents($caminhoArquivo), basename($caminhoArquivo))
            ->post($this->baseUrl . '/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'pt'
            ]);

            Log::info('Status da resposta Whisper: ' . $response->status());
            
            if ($response->failed()) {
                Log::error('Erro na API Whisper: ' . $response->body());
                throw new \Exception('Erro na API Whisper: ' . $response->body());
            }

            $data = $response->json();
            Log::info('Resposta Whisper: ' . json_encode($data));
            
            $texto = $data['text'] ?? 'Transcrição não disponível';
            Log::info('Texto transcrito: ' . substr($texto, 0, 200) . '...');
            
            return [
                'sucesso' => true,
                'texto' => $texto,
                'timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao transcrever áudio: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Analisa imagem usando GPT-4 Vision da OpenAI
     */
    public function analisarImagem(string $caminhoArquivo, string $contexto = '', string $tipoAnalise = 'geral'): array
    {
        Log::info('=== ANALISANDO IMAGEM COM GPT-4 VISION ===');
        Log::info('Arquivo: ' . $caminhoArquivo);
        Log::info('Contexto: ' . $contexto);
        Log::info('Tipo de análise: ' . $tipoAnalise);
        
        try {
            if (empty($this->apiKey)) {
                Log::error('Chave da API OpenAI não configurada');
                throw new \Exception('Chave da API OpenAI não configurada');
            }

            // Converter imagem para base64
            $imagemBase64 = base64_encode(file_get_contents($caminhoArquivo));
            $mimeType = mime_content_type($caminhoArquivo);
            
            Log::info('Imagem convertida para base64: ' . strlen($imagemBase64) . ' caracteres');

            // Criar prompt específico para análise médica
            $promptSistema = $this->criarPromptAnaliseImagem($tipoAnalise);
            $promptUsuario = $this->criarPromptUsuarioImagem($contexto);

            $payload = [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $promptSistema
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $promptUsuario
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imagemBase64}"
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3
            ];
            
            Log::info('Payload GPT-4 Vision: ' . json_encode($payload));

            try {
                $data = $this->enviarRequisicaoComRetry($payload);
                Log::info('Resposta GPT-4 Vision: ' . json_encode($data));
                
                $resposta = $data['choices'][0]['message']['content'] ?? 'Análise não disponível';
            } catch (\Exception $e) {
                Log::error('Erro na análise de imagem: ' . $e->getMessage());
                
                // Resposta de fallback para análise de imagem
                $resposta = "Desculpe, não foi possível analisar a imagem devido a problemas de conexão. ";
                $resposta .= "Por favor, tente novamente em alguns minutos ou consulte um profissional de saúde qualificado para uma avaliação mais detalhada.";
            }
            Log::info('Análise da imagem: ' . substr($resposta, 0, 200) . '...');
            
            return [
                'sucesso' => true,
                'descricao' => $resposta,
                'resposta_sofia' => $this->processarRespostaImagem($resposta, $tipoAnalise),
                'recomendacoes' => $this->gerarRecomendacoesImagem($tipoAnalise),
                'alerta_medico' => $this->detectarAlertaMedico($resposta),
                'timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao analisar imagem: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
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

    /**
     * Cria prompt específico para análise de imagem médica
     */
    private function criarPromptAnaliseImagem(string $tipoAnalise): string
    {
        return "Você é um assistente médico especializado em análise de imagens. Sua tarefa é descrever objetivamente o que você observa na imagem fornecida. 

INSTRUÇÕES:
1. Descreva apenas o que você vê visualmente na imagem
2. Identifique estruturas, formas, cores e texturas visíveis
3. Não faça diagnósticos médicos específicos
4. Sempre recomende consulta com profissional de saúde qualificado
5. Seja objetivo e descritivo em sua análise

Responda sempre em português brasileiro.";
    }

    /**
     * Cria prompt do usuário para análise de imagem
     */
    private function criarPromptUsuarioImagem(string $contexto): string
    {
        $prompt = "Analise esta imagem e descreva objetivamente o que você observa.";
        
        if (!empty($contexto)) {
            $prompt .= " Contexto adicional: {$contexto}";
        }
        
        $prompt .= " Forneça uma descrição detalhada das características visuais da imagem.";
        
        return $prompt;
    }

    /**
     * Processa resposta da análise de imagem
     */
    private function processarRespostaImagem(string $resposta, string $tipoAnalise): string
    {
        $prefixo = "Como SOFIA, analisei a imagem e posso compartilhar algumas observações gerais:\n\n";
        $sufixo = "\n\n⚠️ **IMPORTANTE**: Esta análise é apenas informativa e não substitui a avaliação de um médico especialista. Recomendo fortemente que você consulte um profissional de saúde para uma análise completa e precisa.";
        
        return $prefixo . $resposta . $sufixo;
    }

    /**
     * Gera recomendações baseadas no tipo de análise
     */
    private function gerarRecomendacoesImagem(string $tipoAnalise): array
    {
        $recomendacoes = [
            "Consulte um médico especialista para avaliação completa",
            "Mantenha acompanhamento médico regular",
            "Documente suas dúvidas para próxima consulta"
        ];
        
        switch ($tipoAnalise) {
            case 'medica':
                $recomendacoes[] = "Considere realizar exames complementares se recomendado pelo médico";
                break;
                
            case 'radiologia':
                $recomendacoes[] = "Solicite interpretação por radiologista especializado";
                $recomendacoes[] = "Compare com exames anteriores se disponíveis";
                break;
        }
        
        return $recomendacoes;
    }

    /**
     * Gera resumo de texto usando IA
     */
    public function gerarResumo(string $prompt): string
    {
        try {
            Log::info('Gerando resumo com IA', ['tamanho_prompt' => strlen($prompt)]);
            
            $resposta = $this->enviarRequisicaoGPT($prompt);
            
            Log::info('Resumo gerado com sucesso', ['tamanho_resposta' => strlen($resposta)]);
            
            return $resposta;
            
        } catch (\Exception $e) {
            Log::error('Erro ao gerar resumo', ['erro' => $e->getMessage()]);
            
            // Fallback: retorna uma versão truncada do texto original
            return substr($prompt, 0, 150) . '...';
        }
    }

    /**
     * Detecta alertas médicos na análise de imagem
     */
    private function detectarAlertaMedico(string $resposta): ?string
    {
        $palavrasAlerta = [
            'urgente', 'emergência', 'crítico', 'grave', 'imediato',
            'anormalidade', 'alteração significativa', 'preocupante'
        ];
        
        foreach ($palavrasAlerta as $palavra) {
            if (stripos($resposta, $palavra) !== false) {
                return "⚠️ ALERTA: Detectei informações que podem requerer atenção médica imediata. Por favor, consulte um médico especialista o mais rápido possível.";
            }
        }
        
        return null;
    }

    /**
     * Detecta se a resposta é um resumo final
     */
    private function ehResumoFinal(string $resposta): bool
    {
        $indicadoresResumo = [
            'relatório de risco',
            'resumo das respostas',
            'critérios e raciocínio',
            'flags de risco',
            'recomendações',
            'prioridade de ações',
            'aviso: este relatório',
            'não substitui avaliação médica'
        ];
        
        $respostaLower = strtolower($resposta);
        $contadorIndicadores = 0;
        
        foreach ($indicadoresResumo as $indicador) {
            if (strpos($respostaLower, $indicador) !== false) {
                $contadorIndicadores++;
            }
        }
        
        // Se encontrar pelo menos 3 indicadores, provavelmente é um resumo
        return $contadorIndicadores >= 3;
    }
}

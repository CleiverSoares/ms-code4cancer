<?php

namespace App\Services;

use App\Services\ServicoOpenAIService;
use Illuminate\Support\Facades\Log;

class ServicoChatService
{
    private ServicoOpenAIService $servicoOpenAI;

    public function __construct(ServicoOpenAIService $servicoOpenAI)
    {
        $this->servicoOpenAI = $servicoOpenAI;
    }

    /**
     * Processa mensagem do chat da SOFIA
     */
    public function processarMensagemChat(string $mensagemUsuario, ?array $historicoConversa = null): array
    {
        Log::info('=== PROCESSANDO MENSAGEM CHAT SOFIA ===');
        Log::info('Mensagem: ' . $mensagemUsuario);
        Log::info('Histórico: ' . json_encode($historicoConversa));
        
        try {
            $promptPersonalizado = $this->criarPromptSOFIA($mensagemUsuario, $historicoConversa);
            Log::info('Prompt criado: ' . substr($promptPersonalizado, 0, 200) . '...');
            
            $resposta = $this->servicoOpenAI->processarPergunta($promptPersonalizado);
            Log::info('Resposta OpenAI recebida: ' . substr($resposta['resposta'], 0, 200) . '...');
            
            return [
                'sucesso' => true,
                'mensagem_usuario' => $mensagemUsuario,
                'resposta_sofia' => $resposta['resposta'],
                'timestamp' => now()->toISOString(),
                'personalidade' => 'SOFIA'
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem do chat SOFIA: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return [
                'sucesso' => false,
                'erro' => 'Desculpe, estou com dificuldades técnicas. Tente novamente em alguns instantes.',
                'mensagem_usuario' => $mensagemUsuario,
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Cria prompt personalizado para a SOFIA
     */
    private function criarPromptSOFIA(string $mensagemUsuario, ?array $historicoConversa = null): string
    {
        $contextoHistorico = '';
        
        if ($historicoConversa && count($historicoConversa) > 0) {
            $contextoHistorico = "\n\nContexto da conversa anterior:\n";
            foreach (array_slice($historicoConversa, -3) as $mensagem) {
                $contextoHistorico .= "- {$mensagem}\n";
            }
        }

        return "Você é a SOFIA (Sistema de Orientação e Filtragem Inteligente de Apoio ao Câncer), uma assistente virtual especializada em oncologia e cuidados paliativos.

PERSONALIDADE DA SOFIA:
- Você é empática, profissional e acolhedora
- Sempre prioriza o bem-estar e segurança do paciente
- Fornece informações baseadas em evidências médicas
- Incentiva sempre a consulta com profissionais de saúde
- Usa linguagem clara e acessível
- É especializada em triagem, filtragem e orientação sobre câncer

DIRETRIZES IMPORTANTES:
1. NUNCA forneça diagnósticos médicos específicos
2. SEMPRE recomende consulta com médico especialista
3. Foque em educação, prevenção e suporte emocional
4. Use dados do INCA quando relevante
5. Seja proativa em sugerir exames preventivos
6. Mantenha tom acolhedor mas profissional

Mensagem do usuário: {$mensagemUsuario}{$contextoHistorico}

Responda como a SOFIA, sendo útil, empática e sempre lembrando que você é uma assistente virtual que complementa, mas não substitui, o atendimento médico profissional.";
    }

    /**
     * Analisa intenção da mensagem do usuário
     */
    public function analisarIntencao(string $mensagem): array
    {
        $mensagemLower = strtolower($mensagem);
        
        $intencoes = [
            'sintomas' => ['sintoma', 'dor', 'cansaço', 'fadiga', 'nódulo', 'caroço', 'perda peso'],
            'prevencao' => ['prevenir', 'prevenção', 'evitar', 'risco', 'fator risco'],
            'tratamento' => ['tratamento', 'quimio', 'radio', 'cirurgia', 'terapia'],
            'suporte' => ['suporte', 'emocional', 'psicológico', 'apoio', 'ajuda'],
            'exames' => ['exame', 'mamografia', 'papanicolau', 'psa', 'rastreamento'],
            'alimentacao' => ['alimentação', 'comida', 'dieta', 'nutrição'],
            'exercicio' => ['exercício', 'atividade física', 'caminhada', 'ginástica']
        ];

        $intencaoDetectada = 'geral';
        $confianca = 0;

        foreach ($intencoes as $intencao => $palavras) {
            $matches = 0;
            foreach ($palavras as $palavra) {
                if (strpos($mensagemLower, $palavra) !== false) {
                    $matches++;
                }
            }
            
            if ($matches > $confianca) {
                $confianca = $matches;
                $intencaoDetectada = $intencao;
            }
        }

        return [
            'intencao' => $intencaoDetectada,
            'confianca' => $confianca,
            'palavras_chave_detectadas' => $this->extrairPalavrasChave($mensagemLower, $intencoes)
        ];
    }

    /**
     * Extrai palavras-chave da mensagem
     */
    private function extrairPalavrasChave(string $mensagem, array $intencoes): array
    {
        $palavrasEncontradas = [];
        
        foreach ($intencoes as $intencao => $palavras) {
            foreach ($palavras as $palavra) {
                if (strpos($mensagem, $palavra) !== false) {
                    $palavrasEncontradas[] = $palavra;
                }
            }
        }
        
        return array_unique($palavrasEncontradas);
    }

    /**
     * Gera resposta contextual baseada na intenção
     */
    public function gerarRespostaContextual(string $mensagem): array
    {
        $analiseIntencao = $this->analisarIntencao($mensagem);
        
        $respostaBaseadaIntencao = $this->processarMensagemChat($mensagem);
        
        return array_merge($respostaBaseadaIntencao, [
            'analise_intencao' => $analiseIntencao,
            'sugestoes_proximos_passos' => $this->gerarSugestoesProximosPassos($analiseIntencao['intencao'])
        ]);
    }

    /**
     * Gera sugestões de próximos passos baseadas na intenção
     */
    private function gerarSugestoesProximosPassos(string $intencao): array
    {
        return match($intencao) {
            'sintomas' => [
                'Agendar consulta com médico especialista',
                'Documentar sintomas com datas e intensidade',
                'Considerar exames de rastreamento apropriados'
            ],
            'prevencao' => [
                'Manter estilo de vida saudável',
                'Realizar exames preventivos regulares',
                'Evitar fatores de risco conhecidos'
            ],
            'tratamento' => [
                'Consultar oncologista especializado',
                'Discutir opções de tratamento',
                'Considerar segunda opinião médica'
            ],
            'suporte' => [
                'Buscar grupos de apoio',
                'Considerar acompanhamento psicológico',
                'Manter rede de apoio familiar'
            ],
            'exames' => [
                'Agendar exames conforme recomendação médica',
                'Manter calendário de rastreamento',
                'Discutir resultados com médico'
            ],
            default => [
                'Manter acompanhamento médico regular',
                'Documentar dúvidas para próxima consulta',
                'Buscar informações em fontes confiáveis'
            ]
        };
    }

    /**
     * Valida se a mensagem contém conteúdo apropriado
     */
    public function validarMensagem(string $mensagem): array
    {
        $mensagemLimpa = trim($mensagem);
        
        if (empty($mensagemLimpa)) {
            return [
                'valida' => false,
                'erro' => 'Mensagem não pode estar vazia'
            ];
        }
        
        if (strlen($mensagemLimpa) > 1000) {
            return [
                'valida' => false,
                'erro' => 'Mensagem muito longa (máximo 1000 caracteres)'
            ];
        }
        
        // Detectar possíveis emergências médicas
        $palavrasEmergencia = ['emergência', 'urgente', 'grave', 'socorro', 'ajuda imediata'];
        $mensagemLower = strtolower($mensagemLimpa);
        
        foreach ($palavrasEmergencia as $palavra) {
            if (strpos($mensagemLower, $palavra) !== false) {
                return [
                    'valida' => true,
                    'emergencia_detectada' => true,
                    'alerta' => 'Emergência médica detectada - procure atendimento imediato'
                ];
            }
        }
        
        return [
            'valida' => true,
            'emergencia_detectada' => false
        ];
    }
}

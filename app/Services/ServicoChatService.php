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
        Log::info('Timestamp: ' . now()->toISOString());
        Log::info('Mensagem: ' . $mensagemUsuario);
        Log::info('Tamanho da mensagem: ' . strlen($mensagemUsuario) . ' caracteres');
        Log::info('Histórico: ' . json_encode($historicoConversa));
        Log::info('Quantidade de mensagens no histórico: ' . (is_array($historicoConversa) ? count($historicoConversa) : 0));
        
        try {
            $inicioProcessamento = microtime(true);
            
            $promptPersonalizado = $this->criarPromptSOFIA($mensagemUsuario, $historicoConversa);
            Log::info('Prompt criado: ' . substr($promptPersonalizado, 0, 200) . '...');
            Log::info('Tamanho do prompt: ' . strlen($promptPersonalizado) . ' caracteres');
            
            $resposta = $this->servicoOpenAI->processarPergunta($promptPersonalizado);
            $tempoProcessamento = microtime(true) - $inicioProcessamento;
            
            Log::info('Resposta recebida da OpenAI em ' . round($tempoProcessamento, 3) . ' segundos');
            Log::info('Resposta: ' . substr($resposta['resposta'], 0, 200) . '...');
            Log::info('Tamanho da resposta: ' . strlen($resposta['resposta']) . ' caracteres');
            
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

    /**
     * Processa arquivo de áudio usando Whisper da OpenAI
     */
    public function processarAudio($arquivoAudio): array
    {
        Log::info('=== PROCESSANDO ÁUDIO COM WHISPER ===');
        Log::info('Arquivo: ' . $arquivoAudio->getClientOriginalName());
        Log::info('Tamanho: ' . $arquivoAudio->getSize() . ' bytes');
        
        try {
            // Salvar arquivo temporariamente usando move()
            Log::info('Tentando salvar arquivo de áudio...');
            Log::info('Nome original: ' . $arquivoAudio->getClientOriginalName());
            Log::info('Nome temporário: ' . $arquivoAudio->getFilename());
            Log::info('Caminho temporário: ' . $arquivoAudio->getPathname());
            
            // Criar nome único para o arquivo
            $nomeArquivo = uniqid() . '.' . $arquivoAudio->getClientOriginalExtension();
            $caminhoDestino = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . $nomeArquivo);
            
            Log::info('Caminho destino: ' . $caminhoDestino);
            
            // Mover arquivo diretamente
            $arquivoAudio->move(storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'audio'), $nomeArquivo);
            Log::info('Arquivo de áudio movido com sucesso');
            
            $caminhoCompleto = $caminhoDestino;
            
            Log::info('Arquivo salvo em: ' . $caminhoCompleto);
            Log::info('Arquivo existe: ' . (file_exists($caminhoCompleto) ? 'SIM' : 'NÃO'));
            Log::info('Tamanho do arquivo: ' . filesize($caminhoCompleto) . ' bytes');
            
            // Verificar se arquivo foi salvo corretamente
            if (!file_exists($caminhoCompleto)) {
                throw new \Exception("Arquivo não foi salvo corretamente: {$caminhoCompleto}");
            }
            
            // Transcrever áudio usando Whisper
            $transcricao = $this->servicoOpenAI->transcreverAudio($caminhoCompleto);
            
            // Limpar arquivo temporário
            if (file_exists($caminhoCompleto)) {
                unlink($caminhoCompleto);
                Log::info('Arquivo temporário removido: ' . $caminhoCompleto);
            }
            
            if ($transcricao['sucesso']) {
                // Processar transcrição como mensagem normal
                $resposta = $this->gerarRespostaContextual($transcricao['texto']);
                
                return [
                    'sucesso' => true,
                    'tipo_entrada' => 'audio',
                    'transcricao' => $transcricao['texto'],
                    'resposta_sofia' => $resposta['resposta_sofia'],
                    'analise_intencao' => $resposta['analise_intencao'],
                    'sugestoes_proximos_passos' => $resposta['sugestoes_proximos_passos'],
                    'timestamp' => now()->toISOString()
                ];
            } else {
                return [
                    'sucesso' => false,
                    'erro' => 'Erro ao transcrever áudio: ' . $transcricao['erro'],
                    'timestamp' => now()->toISOString()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar áudio: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro interno ao processar áudio',
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Processa imagem usando GPT-4 Vision da OpenAI
     */
    public function processarImagem($arquivoImagem, string $contexto = '', string $tipoAnalise = 'geral'): array
    {
        Log::info('=== PROCESSANDO IMAGEM COM GPT-4 VISION ===');
        Log::info('Arquivo: ' . $arquivoImagem->getClientOriginalName());
        Log::info('Tamanho: ' . $arquivoImagem->getSize() . ' bytes');
        Log::info('Contexto: ' . $contexto);
        Log::info('Tipo de análise: ' . $tipoAnalise);
        
        try {
            // Salvar arquivo temporariamente usando move()
            Log::info('Tentando salvar arquivo...');
            Log::info('Nome original: ' . $arquivoImagem->getClientOriginalName());
            Log::info('Nome temporário: ' . $arquivoImagem->getFilename());
            Log::info('Caminho temporário: ' . $arquivoImagem->getPathname());
            
            // Criar nome único para o arquivo
            $nomeArquivo = uniqid() . '.' . $arquivoImagem->getClientOriginalExtension();
            $caminhoDestino = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $nomeArquivo);
            
            Log::info('Caminho destino: ' . $caminhoDestino);
            
            // Mover arquivo diretamente
            $arquivoImagem->move(storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'images'), $nomeArquivo);
            Log::info('Arquivo movido com sucesso');
            
            $caminhoCompleto = $caminhoDestino;
            
            Log::info('Arquivo salvo em: ' . $caminhoCompleto);
            Log::info('Storage path: ' . storage_path('app'));
            Log::info('Arquivo existe antes da verificação: ' . (file_exists($caminhoCompleto) ? 'SIM' : 'NÃO'));
            
            // Verificar se arquivo foi salvo corretamente
            if (!file_exists($caminhoCompleto)) {
                throw new \Exception("Arquivo não foi salvo corretamente: {$caminhoCompleto}");
            }
            
            Log::info('Arquivo existe: ' . (file_exists($caminhoCompleto) ? 'SIM' : 'NÃO'));
            Log::info('Tamanho do arquivo: ' . filesize($caminhoCompleto) . ' bytes');
            
            // Analisar imagem usando GPT-4 Vision
            $analise = $this->servicoOpenAI->analisarImagem($caminhoCompleto, $contexto, $tipoAnalise);
            
            // Limpar arquivo temporário
            if (file_exists($caminhoCompleto)) {
                unlink($caminhoCompleto);
                Log::info('Arquivo temporário removido: ' . $caminhoCompleto);
            }
            
            if ($analise['sucesso']) {
                return [
                    'sucesso' => true,
                    'tipo_entrada' => 'imagem',
                    'analise_imagem' => $analise['descricao'],
                    'resposta_sofia' => $analise['resposta_sofia'],
                    'recomendacoes' => $analise['recomendacoes'],
                    'alerta_medico' => $analise['alerta_medico'] ?? null,
                    'timestamp' => now()->toISOString()
                ];
            } else {
                return [
                    'sucesso' => false,
                    'erro' => 'Erro ao analisar imagem: ' . $analise['erro'],
                    'timestamp' => now()->toISOString()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar imagem: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro interno ao processar imagem',
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Processa áudio com dados do usuário para análise personalizada
     */
    public function processarAudioComDados($arquivoAudio, array $dadosUsuario): array
    {
        Log::info('=== PROCESSANDO ÁUDIO COM DADOS DO USUÁRIO ===');
        Log::info('Dados do usuário:', $dadosUsuario);
        Log::info('Arquivo: ' . $arquivoAudio->getClientOriginalName());
        Log::info('Tamanho: ' . $arquivoAudio->getSize() . ' bytes');
        
        try {
            // Primeiro, processar o áudio normalmente
            $resultadoAudio = $this->processarAudio($arquivoAudio);
            
            if (!$resultadoAudio['sucesso']) {
                return $resultadoAudio;
            }
            
            // Criar contexto personalizado com dados do usuário
            $contextoPersonalizado = $this->criarContextoPersonalizado($dadosUsuario, $resultadoAudio['transcricao']);
            
            // Gerar resposta personalizada usando o contexto
            $respostaPersonalizada = $this->gerarRespostaPersonalizada($contextoPersonalizado, $dadosUsuario);
            
            $resultadoFinal = [
                'sucesso' => true,
                'tipo_entrada' => 'audio',
                'transcricao' => $resultadoAudio['transcricao'],
                'resposta_sofia' => $respostaPersonalizada['resposta'],
                'recomendacoes' => $respostaPersonalizada['recomendacoes'],
                'alerta_medico' => $respostaPersonalizada['alerta_medico'],
                'dados_usuario' => $dadosUsuario,
                'timestamp' => now()->toISOString()
            ];
            
            // Verificar se deve enviar alerta médico automaticamente
            $this->verificarEEnviarAlertaAutomatico($resultadoFinal);
            
            return $resultadoFinal;
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar áudio com dados: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro interno ao processar áudio com dados do usuário',
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Processa imagem com dados do usuário para análise personalizada
     */
    public function processarImagemComDados($arquivoImagem, array $dadosUsuario): array
    {
        Log::info('=== PROCESSANDO IMAGEM COM DADOS DO USUÁRIO ===');
        Log::info('Dados do usuário:', $dadosUsuario);
        Log::info('Arquivo: ' . $arquivoImagem->getClientOriginalName());
        Log::info('Tamanho: ' . $arquivoImagem->getSize() . ' bytes');
        
        try {
            // Criar contexto baseado nos dados do usuário
            $contexto = $this->criarContextoImagem($dadosUsuario);
            $tipoAnalise = $this->determinarTipoAnaliseImagem($dadosUsuario);
            
            // Processar imagem normalmente
            $resultadoImagem = $this->processarImagem($arquivoImagem, $contexto, $tipoAnalise);
            
            if (!$resultadoImagem['sucesso']) {
                return $resultadoImagem;
            }
            
            // Personalizar resposta com dados do usuário
            $respostaPersonalizada = $this->personalizarRespostaImagem($resultadoImagem, $dadosUsuario);
            
            $resultadoFinal = [
                'sucesso' => true,
                'tipo_entrada' => 'imagem',
                'analise_imagem' => $resultadoImagem['analise_imagem'],
                'resposta_sofia' => $respostaPersonalizada['resposta'],
                'recomendacoes' => $respostaPersonalizada['recomendacoes'],
                'alerta_medico' => $respostaPersonalizada['alerta_medico'],
                'dados_usuario' => $dadosUsuario,
                'timestamp' => now()->toISOString()
            ];
            
            // Verificar se deve enviar alerta médico automaticamente
            $this->verificarEEnviarAlertaAutomatico($resultadoFinal);
            
            return $resultadoFinal;
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar imagem com dados: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro interno ao processar imagem com dados do usuário',
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Cria contexto personalizado com dados do usuário
     */
    private function criarContextoPersonalizado(array $dadosUsuario, string $transcricao): string
    {
        $contexto = "DADOS DO PACIENTE:\n";
        $contexto .= "- Nome: {$dadosUsuario['nome']}\n";
        $contexto .= "- Idade: {$dadosUsuario['idade']} anos\n";
        $contexto .= "- Sexo: " . ($dadosUsuario['sexo'] === 'M' ? 'Masculino' : 'Feminino') . "\n";
        $contexto .= "- Contexto da consulta: {$dadosUsuario['contexto']}\n";
        
        if (!empty($dadosUsuario['descricao'])) {
            $contexto .= "- Descrição adicional: {$dadosUsuario['descricao']}\n";
        }
        
        $contexto .= "\nTRANSCRIÇÃO DO ÁUDIO:\n";
        $contexto .= $transcricao;
        
        return $contexto;
    }

    /**
     * Cria contexto para análise de imagem
     */
    private function criarContextoImagem(array $dadosUsuario): string
    {
        $contexto = "Análise de imagem médica para paciente: {$dadosUsuario['nome']}, ";
        $contexto .= "{$dadosUsuario['idade']} anos, ";
        $contexto .= ($dadosUsuario['sexo'] === 'M' ? 'masculino' : 'feminino') . ". ";
        $contexto .= "Contexto: {$dadosUsuario['contexto']}";
        
        if (!empty($dadosUsuario['descricao'])) {
            $contexto .= ". Informações adicionais: {$dadosUsuario['descricao']}";
        }
        
        return $contexto;
    }

    /**
     * Determina o tipo de análise de imagem baseado no contexto
     */
    private function determinarTipoAnaliseImagem(array $dadosUsuario): string
    {
        return match($dadosUsuario['contexto']) {
            'sintomas' => 'analise_sintomas',
            'exame' => 'analise_exame',
            'duvida' => 'analise_geral',
            'prevencao' => 'analise_prevencao',
            default => 'analise_geral'
        };
    }

    /**
     * Gera resposta personalizada baseada no contexto
     */
    private function gerarRespostaPersonalizada(string $contexto, array $dadosUsuario): array
    {
        try {
            $promptPersonalizado = $this->criarPromptPersonalizado($contexto, $dadosUsuario);
            $resposta = $this->servicoOpenAI->processarPergunta($promptPersonalizado);
            
            // Extrair recomendações e alertas da resposta
            $recomendacoes = $this->extrairRecomendacoes($resposta['resposta']);
            $alertaMedico = $this->detectarAlertaMedico($resposta['resposta'], $dadosUsuario);
            
            return [
                'resposta' => $resposta['resposta'],
                'recomendacoes' => $recomendacoes,
                'alerta_medico' => $alertaMedico
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao gerar resposta personalizada: ' . $e->getMessage());
            
            return [
                'resposta' => 'Desculpe, não foi possível processar sua solicitação no momento. Recomendo consultar um médico especialista.',
                'recomendacoes' => ['Consulte um médico especialista', 'Mantenha acompanhamento médico regular'],
                'alerta_medico' => null
            ];
        }
    }

    /**
     * Personaliza resposta de imagem com dados do usuário
     */
    private function personalizarRespostaImagem(array $resultadoImagem, array $dadosUsuario): array
    {
        try {
            $contextoPersonalizado = "Baseado na análise da imagem e nos dados do paciente ({$dadosUsuario['nome']}, {$dadosUsuario['idade']} anos, {$dadosUsuario['contexto']}), ";
            $contextoPersonalizado .= "personalize a resposta da SOFIA considerando o perfil do paciente.\n\n";
            $contextoPersonalizado .= "Análise da imagem: {$resultadoImagem['analise_imagem']}\n\n";
            $contextoPersonalizado .= "Resposta original: {$resultadoImagem['resposta_sofia']}";
            
            $promptPersonalizado = $this->criarPromptPersonalizado($contextoPersonalizado, $dadosUsuario);
            $resposta = $this->servicoOpenAI->processarPergunta($promptPersonalizado);
            
            return [
                'resposta' => $resposta['resposta'],
                'recomendacoes' => $resultadoImagem['recomendacoes'] ?? [],
                'alerta_medico' => $resultadoImagem['alerta_medico'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao personalizar resposta de imagem: ' . $e->getMessage());
            
            return [
                'resposta' => $resultadoImagem['resposta_sofia'],
                'recomendacoes' => $resultadoImagem['recomendacoes'] ?? [],
                'alerta_medico' => $resultadoImagem['alerta_medico'] ?? null
            ];
        }
    }

    /**
     * Cria prompt personalizado para análise com dados do usuário
     */
    private function criarPromptPersonalizado(string $contexto, array $dadosUsuario): string
    {
        $idade = $dadosUsuario['idade'];
        $sexo = $dadosUsuario['sexo'] === 'M' ? 'masculino' : 'feminino';
        $contextoConsulta = $dadosUsuario['contexto'];
        
        return "Você é a SOFIA (Sistema de Orientação e Filtragem Inteligente de Apoio ao Câncer), uma assistente virtual especializada em oncologia.

DADOS DO PACIENTE:
- Nome: {$dadosUsuario['nome']}
- Idade: {$idade} anos
- Sexo: {$sexo}
- Contexto da consulta: {$contextoConsulta}

CONTEXTO DA ANÁLISE:
{$contexto}

INSTRUÇÕES ESPECÍFICAS:
1. Personalize sua resposta considerando a idade ({$idade} anos) e sexo ({$sexo}) do paciente
2. Adapte o tom e linguagem para o contexto: {$contextoConsulta}
3. Forneça orientações específicas baseadas no perfil demográfico
4. SEMPRE recomende consulta médica especializada
5. Seja empática e acolhedora, mas profissional
6. Considere fatores de risco específicos para a faixa etária e sexo
7. Forneça recomendações práticas e acionáveis

Responda como a SOFIA, sendo útil, empática e sempre lembrando que você é uma assistente virtual que complementa, mas não substitui, o atendimento médico profissional.";
    }

    /**
     * Extrai recomendações da resposta da IA
     */
    private function extrairRecomendacoes(string $resposta): array
    {
        $recomendacoes = [];
        
        // Buscar por padrões de recomendações
        if (preg_match_all('/•\s*([^•\n]+)/', $resposta, $matches)) {
            $recomendacoes = array_map('trim', $matches[1]);
        } elseif (preg_match_all('/-\s*([^-\n]+)/', $resposta, $matches)) {
            $recomendacoes = array_map('trim', $matches[1]);
        } elseif (preg_match_all('/\d+\.\s*([^\d\n]+)/', $resposta, $matches)) {
            $recomendacoes = array_map('trim', $matches[1]);
        }
        
        // Se não encontrou recomendações estruturadas, criar algumas genéricas
        if (empty($recomendacoes)) {
            $recomendacoes = [
                'Consulte um médico especialista para avaliação completa',
                'Mantenha acompanhamento médico regular',
                'Documente sintomas e mudanças observadas'
            ];
        }
        
        return array_slice($recomendacoes, 0, 5); // Máximo 5 recomendações
    }

    /**
     * Verificar e enviar alerta médico automaticamente se necessário
     */
    private function verificarEEnviarAlertaAutomatico(array $resultadoAnalise): void
    {
        try {
            Log::info('=== VERIFICANDO NECESSIDADE DE ALERTA MÉDICO AUTOMÁTICO ===');
            
            // Instanciar serviço de email de alerta
            $servicoEmailAlerta = new \App\Services\ServicoEmailAlertaMidiaService();
            
            // Verificar se deve enviar alerta
            if ($servicoEmailAlerta->deveEnviarAlerta($resultadoAnalise)) {
                Log::info('🚨 CRITÉRIOS PARA ALERTA ATENDIDOS - ENVIANDO EMAIL AUTOMATICAMENTE');
                
                // Enviar alerta automaticamente
                $resultadoEnvio = $servicoEmailAlerta->enviarAlertaAnaliseMidia($resultadoAnalise);
                
                if ($resultadoEnvio['sucesso']) {
                    Log::info('✅ Alerta médico enviado automaticamente com sucesso:', $resultadoEnvio);
                    
                    // Adicionar informação de alerta enviado ao resultado
                    $resultadoAnalise['alerta_enviado_automaticamente'] = true;
                    $resultadoAnalise['emails_enviados'] = $resultadoEnvio['total_enviados'];
                } else {
                    Log::error('❌ Erro ao enviar alerta médico automaticamente:', $resultadoEnvio);
                    $resultadoAnalise['erro_alerta_automatico'] = $resultadoEnvio['mensagem'] ?? 'Erro interno';
                }
            } else {
                Log::info('ℹ️ Critérios para alerta não atendidos - não enviando email');
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao verificar/enviar alerta automático: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Detecta alertas médicos na resposta
     */
    private function detectarAlertaMedico(string $resposta, array $dadosUsuario): ?string
    {
        $palavrasAlerta = [
            'urgente', 'emergência', 'imediato', 'grave', 'socorro',
            'procure médico', 'consulte imediatamente', 'atenção'
        ];
        
        $respostaLower = strtolower($resposta);
        
        foreach ($palavrasAlerta as $palavra) {
            if (strpos($respostaLower, $palavra) !== false) {
                return "Baseado na análise, recomenda-se atenção médica imediata. Consulte um médico especialista o quanto antes.";
            }
        }
        
        // Alertas específicos por idade
        if ($dadosUsuario['idade'] >= 50) {
            return "Considerando sua idade ({$dadosUsuario['idade']} anos), recomenda-se atenção especial aos exames de rastreamento preventivo.";
        }
        
        return null;
    }

    /**
     * Testa conectividade com OpenAI
     */
    public function testarConexao(): array
    {
        Log::info('=== TESTE DE CONECTIVIDADE SOFIA ===');
        
        try {
            $resposta = $this->servicoOpenAI->processarPergunta('Teste de conectividade');
            
            return [
                'sucesso' => true,
                'mensagem' => 'SOFIA está online e funcionando',
                'timestamp' => now()->toISOString(),
                'resposta_teste' => substr($resposta['resposta'], 0, 100) . '...'
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro no teste de conectividade: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'SOFIA está offline',
                'timestamp' => now()->toISOString()
            ];
        }
    }
}

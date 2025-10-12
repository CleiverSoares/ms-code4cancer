<?php

namespace App\Services;

use App\Models\QuestionarioModel;
use App\Repositories\IQuestionarioRepository;
use App\Services\ServicoEmailAlertaService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServicoQuestionarioService
{
    private IQuestionarioRepository $questionarioRepository;
    private ServicoEmailAlertaService $servicoEmailAlerta;

    public function __construct(
        IQuestionarioRepository $questionarioRepository,
        ServicoEmailAlertaService $servicoEmailAlerta
    ) {
        $this->questionarioRepository = $questionarioRepository;
        $this->servicoEmailAlerta = $servicoEmailAlerta;
    }

    /**
     * Processar dados do questionário vindos do frontend (progressivo)
     */
    public function processarQuestionario(int $usuarioId, array $dadosFrontend): array
    {
        try {
            Log::info("📋 Processando questionário progressivo para usuário ID: {$usuarioId}");
            Log::info("📋 Dados recebidos: " . json_encode($dadosFrontend));
            
            // Validar e processar dados básicos (apenas os enviados)
            $dadosProcessados = $this->processarDadosBasicos($dadosFrontend);
            Log::info("📋 Dados básicos processados: " . json_encode($dadosProcessados));
            
            // Calcular campos derivados apenas se possível
            $dadosProcessados = $this->calcularCamposDerivados($dadosProcessados);
            Log::info("📋 Campos derivados calculados: " . json_encode($dadosProcessados));
            
            // Adicionar ID do usuário
            $dadosProcessados['usuario_id'] = $usuarioId;
            Log::info("📋 Dados finais para salvar: " . json_encode($dadosProcessados));
            
            // Salvar/atualizar no banco (merge com dados existentes)
            $questionario = $this->salvarProgressivamente($usuarioId, $dadosProcessados);
            Log::info("📋 Questionário salvo no banco: " . json_encode($questionario->toArray()));
            
            // Calcular análise de risco (baseada nos dados disponíveis)
            $analiseRisco = $this->calcularAnaliseRiscoProgressiva($questionario);
            
            // Gerar recomendações personalizadas (baseadas nos dados disponíveis)
            $recomendacoes = $this->gerarRecomendacoesProgressivas($questionario);
            
            // Registrar atividade de gamificação (pontos por progresso)
            $gamificacao = $this->registrarGamificacaoProgressiva($usuarioId, $questionario, $dadosProcessados);
            
            // Processar alertas de atendimento prioritário
            $alertaEmail = $this->servicoEmailAlerta->processarQuestionario($questionario);
            
            // Calcular progresso do questionário
            $progressoQuestionario = $this->calcularProgressoQuestionario($questionario);
            
            Log::info("✅ Questionário processado com sucesso para usuário ID: {$usuarioId}");
            
            return [
                'sucesso' => true,
                'questionario' => $questionario,
                'analise_risco' => $analiseRisco,
                'recomendacoes' => $recomendacoes,
                'gamificacao' => $gamificacao,
                'alerta_email' => $alertaEmail,
                'progresso_questionario' => $progressoQuestionario,
                'estatisticas_pessoais' => $this->calcularEstatisticasPessoais($questionario),
                'proximas_perguntas' => $this->sugerirProximasPerguntas($questionario)
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Erro ao processar questionário: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processar dados básicos vindos do frontend
     */
    private function processarDadosBasicos(array $dadosFrontend): array
    {
        $dadosProcessados = [];
        
        // Mapear campos do frontend para o banco
        $mapeamentoCampos = [
            'nomeCompleto' => 'nome_completo',
            'dataNascimento' => 'data_nascimento',
            'sexoBiologico' => 'sexo_biologico',
            'atividadeSexual' => 'atividade_sexual',
            'pesoKg' => 'peso_kg',
            'alturaCm' => 'altura_cm',
            'cidade' => 'cidade',
            'estado' => 'estado',
            'teveCancerPessoal' => 'teve_cancer_pessoal',
            'parente1GrauCancer' => 'parente_1grau_cancer',
            'tipoCancerParente' => 'tipo_cancer_parente',
            'idadeDiagnosticoParente' => 'idade_diagnostico_parente',
            'statusTabagismo' => 'status_tabagismo',
            'macosDia' => 'macos_dia',
            'anosFumando' => 'anos_fumando',
            'consomeAlcool' => 'consome_alcool',
            'praticaAtividade' => 'pratica_atividade',
            'idadePrimeiraMenstruacao' => 'idade_primeira_menstruacao',
            'jaEngravidou' => 'ja_engravidou',
            'usoAnticoncepcional' => 'uso_anticoncepcional',
            'fezPapanicolau' => 'fez_papanicolau',
            'anoUltimoPapanicolau' => 'ano_ultimo_papanicolau',
            'fezMamografia' => 'fez_mamografia',
            'anoUltimaMamografia' => 'ano_ultima_mamografia',
            'histFamMamaOvario' => 'hist_fam_mama_ovario',
            'fezRastreamentoProstata' => 'fez_rastreamento_prostata',
            'desejaInfoProstata' => 'deseja_info_prostata',
            'parente1GrauColorretal' => 'parente_1grau_colorretal',
            'fezExameColorretal' => 'fez_exame_colorretal',
            'anoUltimoExameColorretal' => 'ano_ultimo_exame_colorretal',
            'sinaisAlertaIntestino' => 'sinais_alerta_intestino',
            'sangramentoAnormal' => 'sangramento_anormal',
            'tossePersistente' => 'tosse_persistente',
            'nodulosPalpaveis' => 'nodulos_palpaveis',
            'perdaPesoNaoIntencional' => 'perda_peso_nao_intencional',
            'precisaAtendimentoPrioritario' => 'precisa_atendimento_prioritario'
        ];
        
        foreach ($mapeamentoCampos as $frontend => $backend) {
            if (isset($dadosFrontend[$frontend])) {
                $dadosProcessados[$backend] = $dadosFrontend[$frontend];
            }
        }
        
        return $dadosProcessados;
    }

    /**
     * Calcular campos derivados
     */
    private function calcularCamposDerivados(array $dados): array
    {
        // Calcular idade
        if (isset($dados['data_nascimento'])) {
            $idade = Carbon::parse($dados['data_nascimento'])->age;
            $dados['mais_de_45_anos'] = $idade >= 45;
        }
        
        return $dados;
    }

    /**
     * Salvar questionário progressivamente (merge com dados existentes)
     */
    private function salvarProgressivamente(int $usuarioId, array $dadosNovos): QuestionarioModel
    {
        Log::info("💾 Salvando questionário progressivamente para usuário ID: {$usuarioId}");
        Log::info("💾 Dados novos: " . json_encode($dadosNovos));
        
        $questionarioExistente = $this->questionarioRepository->buscarPorUsuario($usuarioId);
        Log::info("💾 Questionário existente: " . ($questionarioExistente ? "SIM" : "NÃO"));
        
        if ($questionarioExistente) {
            Log::info("💾 Atualizando questionário existente ID: {$questionarioExistente->id}");
            // Merge com dados existentes (apenas campos não nulos)
            $dadosAtualizados = [];
            foreach ($dadosNovos as $campo => $valor) {
                if ($valor !== null && $valor !== '') {
                    $dadosAtualizados[$campo] = $valor;
                }
            }
            Log::info("💾 Dados para atualizar: " . json_encode($dadosAtualizados));
            
            $questionarioExistente->update($dadosAtualizados);
            $questionarioAtualizado = $questionarioExistente->fresh();
            Log::info("💾 Questionário atualizado: " . json_encode($questionarioAtualizado->toArray()));
            return $questionarioAtualizado;
        } else {
            Log::info("💾 Criando novo questionário");
            
            // Garantir que campos obrigatórios tenham valores padrão
            $dadosComDefaults = $this->aplicarValoresPadrao($dadosNovos);
            Log::info("💾 Dados com valores padrão: " . json_encode($dadosComDefaults));
            
            $novoQuestionario = $this->questionarioRepository->salvar($dadosComDefaults);
            Log::info("💾 Novo questionário criado: " . json_encode($novoQuestionario->toArray()));
            return $novoQuestionario;
        }
    }

    /**
     * Aplicar valores padrão para campos obrigatórios ao criar novo questionário
     */
    private function aplicarValoresPadrao(array $dados): array
    {
        $dadosComDefaults = $dados;
        
        // Campos obrigatórios que precisam de valores padrão
        $valoresPadrao = [
            'sexo_biologico' => 'O', // Outro como padrão neutro
            'atividade_sexual' => false, // Padrão conservador
            'precisa_atendimento_prioritario' => false
        ];
        
        // Aplicar valores padrão apenas se o campo não estiver presente
        foreach ($valoresPadrao as $campo => $valorPadrao) {
            if (!isset($dadosComDefaults[$campo]) || $dadosComDefaults[$campo] === null || $dadosComDefaults[$campo] === '') {
                $dadosComDefaults[$campo] = $valorPadrao;
                Log::info("🔧 Aplicado valor padrão para {$campo}: {$valorPadrao}");
            }
        }
        
        return $dadosComDefaults;
    }

    /**
     * Calcular análise de risco progressiva (baseada nos dados disponíveis)
     */
    private function calcularAnaliseRiscoProgressiva(QuestionarioModel $questionario): array
    {
        $analise = [
            'risco_geral' => 'indeterminado',
            'pontuacao_risco' => 0,
            'fatores_risco' => [],
            'fatores_protecao' => [],
            'sinais_alerta' => [],
            'elegibilidades' => [],
            'dados_suficientes' => false,
            'campos_faltando' => []
        ];
        
        $camposObrigatorios = ['data_nascimento', 'sexo_biologico'];
        $camposFaltando = [];
        
        foreach ($camposObrigatorios as $campo) {
            if (!$questionario->$campo) {
                $camposFaltando[] = $campo;
            }
        }
        
        $analise['campos_faltando'] = $camposFaltando;
        $analise['dados_suficientes'] = empty($camposFaltando);
        
        if (!$analise['dados_suficientes']) {
            return $analise;
        }
        
        // Calcular risco apenas com dados disponíveis
        $pontuacao = 0;
        
        if ($questionario->status_tabagismo === 'Sim') {
            $pontuacao += 30;
            $analise['fatores_risco'][] = 'Tabagismo ativo';
        } elseif ($questionario->status_tabagismo === 'Ex-fumante') {
            $pontuacao += 15;
            $analise['fatores_risco'][] = 'Histórico de tabagismo';
        }
        
        if ($questionario->consome_alcool) {
            $pontuacao += 10;
            $analise['fatores_risco'][] = 'Consumo de álcool';
        }
        
        if ($questionario->parente_1grau_cancer) {
            $pontuacao += 20;
            $analise['fatores_risco'][] = 'Histórico familiar de câncer';
        }
        
        if (!$questionario->pratica_atividade) {
            $pontuacao += 10;
            $analise['fatores_risco'][] = 'Sedentarismo';
        }
        
        // Fatores de proteção
        if ($questionario->pratica_atividade) {
            $pontuacao -= 5;
            $analise['fatores_protecao'][] = 'Atividade física regular';
        }
        
        if (!$questionario->consome_alcool) {
            $pontuacao -= 5;
            $analise['fatores_protecao'][] = 'Não consome álcool';
        }
        
        // Sinais de alerta
        if ($questionario->sangramento_anormal) {
            $pontuacao += 25;
            $analise['sinais_alerta'][] = 'Sangramento anormal';
        }
        
        if ($questionario->tosse_persistente) {
            $pontuacao += 20;
            $analise['sinais_alerta'][] = 'Tosse persistente';
        }
        
        if ($questionario->nodulos_palpaveis) {
            $pontuacao += 25;
            $analise['sinais_alerta'][] = 'Nódulos palpáveis';
        }
        
        if ($questionario->perda_peso_nao_intencional) {
            $pontuacao += 20;
            $analise['sinais_alerta'][] = 'Perda de peso não intencional';
        }
        
        if ($questionario->sinais_alerta_intestino) {
            $pontuacao += 20;
            $analise['sinais_alerta'][] = 'Sinais de alerta intestinal';
        }
        
        // Determinar nível de risco
        if ($pontuacao >= 50) {
            $analise['risco_geral'] = 'alto';
        } elseif ($pontuacao >= 25) {
            $analise['risco_geral'] = 'moderado';
        } elseif ($pontuacao > 0) {
            $analise['risco_geral'] = 'baixo';
        } else {
            $analise['risco_geral'] = 'muito_baixo';
        }
        
        $analise['pontuacao_risco'] = $pontuacao;
        
        // Elegibilidades (apenas se tiver dados suficientes)
        if ($questionario->data_nascimento && $questionario->sexo_biologico) {
            $analise['elegibilidades'] = [
                'cervical' => $questionario->elegivelRastreamentoCervical(),
                'mamografia' => $questionario->elegivelMamografia(),
                'prostata' => $questionario->elegivelRastreamentoProstata(),
                'colorretal' => $questionario->elegivelRastreamentoColorretal()
            ];
        }
        
        return $analise;
    }

    /**
     * Gerar recomendações progressivas (baseadas nos dados disponíveis)
     */
    private function gerarRecomendacoesProgressivas(QuestionarioModel $questionario): array
    {
        $recomendacoes = [];
        
        // Recomendações básicas sempre disponíveis
        $recomendacoes[] = [
            'tipo' => 'geral',
            'categoria' => 'prevencao',
            'titulo' => 'Continue Preenchendo o Questionário',
            'descricao' => 'Complete mais informações para receber recomendações personalizadas',
            'prioridade' => 'baixa',
            'prazo' => 'Contínuo',
            'pontos_gamificacao' => 10
        ];
        
        // Recomendações baseadas em dados disponíveis
        if ($questionario->data_nascimento && $questionario->sexo_biologico) {
            $idade = $questionario->calcularIdade();
            $sexo = $questionario->sexo_biologico;
            
            if ($sexo === 'F' && $idade >= 21 && $questionario->atividade_sexual) {
                $recomendacoes[] = [
                    'tipo' => 'rastreamento',
                    'categoria' => 'cervical',
                    'titulo' => 'Papanicolau',
                    'descricao' => 'Exame de rastreamento do câncer cervical',
                    'prioridade' => 'alta',
                    'prazo' => 'A cada 3 anos',
                    'pontos_gamificacao' => 50
                ];
            }
            
            if ($sexo === 'F' && $idade >= 40) {
                $recomendacoes[] = [
                    'tipo' => 'rastreamento',
                    'categoria' => 'mamario',
                    'titulo' => 'Mamografia',
                    'descricao' => 'Exame de rastreamento do câncer de mama',
                    'prioridade' => 'alta',
                    'prazo' => 'Anual',
                    'pontos_gamificacao' => 50
                ];
            }
            
            if ($sexo === 'M' && $idade >= 50) {
                $recomendacoes[] = [
                    'tipo' => 'rastreamento',
                    'categoria' => 'prostata',
                    'titulo' => 'Discussão sobre PSA',
                    'descricao' => 'Conversar com médico sobre rastreamento de câncer de próstata',
                    'prioridade' => 'media',
                    'prazo' => 'Anual',
                    'pontos_gamificacao' => 30
                ];
            }
            
            if ($idade >= 45) {
                $recomendacoes[] = [
                    'tipo' => 'rastreamento',
                    'categoria' => 'colorretal',
                    'titulo' => 'Rastreamento Colorretal',
                    'descricao' => 'Colonoscopia a cada 10 anos ou teste de sangue oculto anual',
                    'prioridade' => 'alta',
                    'prazo' => 'Conforme orientação médica',
                    'pontos_gamificacao' => 50
                ];
            }
        }
        
        // Recomendações de prevenção baseadas em dados disponíveis
        if ($questionario->status_tabagismo === 'Sim') {
            $recomendacoes[] = [
                'tipo' => 'prevencao',
                'categoria' => 'tabagismo',
                'titulo' => 'Parar de Fumar',
                'descricao' => 'O tabagismo é o principal fator de risco evitável para câncer',
                'prioridade' => 'urgente',
                'prazo' => 'Imediato',
                'pontos_gamificacao' => 100
            ];
        }
        
        if (!$questionario->pratica_atividade) {
            $recomendacoes[] = [
                'tipo' => 'prevencao',
                'categoria' => 'atividade_fisica',
                'titulo' => 'Atividade Física',
                'descricao' => 'Praticar pelo menos 150 minutos de atividade física por semana',
                'prioridade' => 'media',
                'prazo' => 'Contínuo',
                'pontos_gamificacao' => 30
            ];
        }
        
        if ($questionario->consome_alcool) {
            $recomendacoes[] = [
                'tipo' => 'prevencao',
                'categoria' => 'alcool',
                'titulo' => 'Moderar Consumo de Álcool',
                'descricao' => 'Limitar consumo de álcool para reduzir risco de câncer',
                'prioridade' => 'media',
                'prazo' => 'Contínuo',
                'pontos_gamificacao' => 20
            ];
        }
        
        // Sinais de alerta
        if ($questionario->temSinaisAlerta()) {
            $recomendacoes[] = [
                'tipo' => 'alerta',
                'categoria' => 'urgente',
                'titulo' => 'Avaliação Médica Urgente',
                'descricao' => 'Você relatou sinais que merecem avaliação médica imediata',
                'prioridade' => 'urgente',
                'prazo' => 'Imediato',
                'pontos_gamificacao' => 0
            ];
        }
        
        return $recomendacoes;
    }

    /**
     * Registrar gamificação progressiva (pontos por progresso)
     */
    private function registrarGamificacaoProgressiva(int $usuarioId, QuestionarioModel $questionario, array $dadosNovos): array
    {
        try {
            $pontosExtras = 0;
            $camposPreenchidos = count(array_filter($dadosNovos, function($valor) {
                return $valor !== null && $valor !== '';
            }));
            
            // Pontos por campos preenchidos
            $pontosExtras += $camposPreenchidos * 2;
            
            // Pontos extras por sinais de alerta (incentiva transparência)
            if ($questionario->temSinaisAlerta()) {
                $pontosExtras += 20;
            }
            
            // Gamificação removida - não há implementação no frontend
            return ['sucesso' => true, 'mensagem' => 'Questionário atualizado sem gamificação'];
            
        } catch (\Exception $e) {
            Log::warning('Erro ao registrar gamificação progressiva: ' . $e->getMessage());
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Calcular progresso do questionário
     */
    private function calcularProgressoQuestionario(QuestionarioModel $questionario): array
    {
        $camposTotais = 33; // Total de campos do questionário
        $camposPreenchidos = 0;
        
        $campos = [
            'nome_completo', 'data_nascimento', 'sexo_biologico', 'atividade_sexual',
            'peso_kg', 'altura_cm', 'cidade', 'estado', 'teve_cancer_pessoal',
            'parente_1grau_cancer', 'tipo_cancer_parente', 'idade_diagnostico_parente',
            'status_tabagismo', 'macos_dia', 'anos_fumando', 'consome_alcool',
            'pratica_atividade', 'idade_primeira_menstruacao', 'ja_engravidou',
            'uso_anticoncepcional', 'fez_papanicolau', 'ano_ultimo_papanicolau',
            'fez_mamografia', 'ano_ultima_mamografia', 'hist_fam_mama_ovario',
            'fez_rastreamento_prostata', 'deseja_info_prostata', 'mais_de_45_anos',
            'parente_1grau_colorretal', 'fez_exame_colorretal', 'ano_ultimo_exame_colorretal',
            'sinais_alerta_intestino', 'sangramento_anormal', 'tosse_persistente',
            'nodulos_palpaveis', 'perda_peso_nao_intencional'
        ];
        
        foreach ($campos as $campo) {
            if ($questionario->$campo !== null && $questionario->$campo !== '') {
                $camposPreenchidos++;
            }
        }
        
        $percentual = round(($camposPreenchidos / $camposTotais) * 100, 1);
        
        return [
            'campos_preenchidos' => $camposPreenchidos,
            'campos_totais' => $camposTotais,
            'percentual' => $percentual,
            'status' => $this->categorizarProgresso($percentual)
        ];
    }

    /**
     * Categorizar progresso
     */
    private function categorizarProgresso(float $percentual): string
    {
        if ($percentual < 25) return 'inicial';
        if ($percentual < 50) return 'básico';
        if ($percentual < 75) return 'intermediário';
        if ($percentual < 100) return 'avançado';
        return 'completo';
    }

    /**
     * Sugerir próximas perguntas baseadas nos dados atuais
     */
    private function sugerirProximasPerguntas(QuestionarioModel $questionario): array
    {
        $sugestoes = [];
        
        // Perguntas básicas sempre sugeridas se não preenchidas
        if (!$questionario->data_nascimento) {
            $sugestoes[] = [
                'campo' => 'data_nascimento',
                'pergunta' => 'Qual sua data de nascimento?',
                'tipo' => 'date',
                'prioridade' => 'alta',
                'motivo' => 'Necessário para calcular idade e elegibilidades'
            ];
        }
        
        if (!$questionario->sexo_biologico) {
            $sugestoes[] = [
                'campo' => 'sexo_biologico',
                'pergunta' => 'Qual seu sexo biológico?',
                'tipo' => 'select',
                'opcoes' => ['F', 'M', 'O'],
                'prioridade' => 'alta',
                'motivo' => 'Necessário para recomendações específicas'
            ];
        }
        
        // Perguntas específicas por sexo
        if ($questionario->sexo_biologico === 'F' && !$questionario->atividade_sexual) {
            $sugestoes[] = [
                'campo' => 'atividade_sexual',
                'pergunta' => 'Você já teve atividade sexual?',
                'tipo' => 'boolean',
                'prioridade' => 'media',
                'motivo' => 'Necessário para elegibilidade ao rastreamento cervical'
            ];
        }
        
        // Perguntas sobre fatores de risco
        if (!$questionario->status_tabagismo) {
            $sugestoes[] = [
                'campo' => 'status_tabagismo',
                'pergunta' => 'Você fuma ou já fumou?',
                'tipo' => 'select',
                'opcoes' => ['Nunca', 'Ex-fumante', 'Sim'],
                'prioridade' => 'alta',
                'motivo' => 'Tabagismo é o principal fator de risco evitável'
            ];
        }
        
        if (!$questionario->parente_1grau_cancer) {
            $sugestoes[] = [
                'campo' => 'parente_1grau_cancer',
                'pergunta' => 'Algum parente de primeiro grau já teve câncer?',
                'tipo' => 'boolean',
                'prioridade' => 'media',
                'motivo' => 'Histórico familiar aumenta o risco'
            ];
        }
        
        return $sugestoes;
    }

    /**
     * Calcular estatísticas pessoais
     */
    private function calcularEstatisticasPessoais(QuestionarioModel $questionario): array
    {
        return [
            'idade' => $questionario->calcularIdade(),
            'imc' => $questionario->calcularIMC(),
            'categoria_imc' => $this->categorizarIMC($questionario->calcularIMC()),
            'faixa_etaria' => $this->categorizarFaixaEtaria($questionario->calcularIdade()),
            'tempo_desde_preenchimento' => $questionario->data_preenchimento->diffForHumans()
        ];
    }

    /**
     * Categorizar IMC
     */
    private function categorizarIMC(?float $imc): ?string
    {
        if (!$imc) return null;
        
        if ($imc < 18.5) return 'Abaixo do peso';
        if ($imc < 25) return 'Peso normal';
        if ($imc < 30) return 'Sobrepeso';
        return 'Obesidade';
    }

    /**
     * Categorizar faixa etária
     */
    private function categorizarFaixaEtaria(int $idade): string
    {
        if ($idade < 30) return '18-29 anos';
        if ($idade < 40) return '30-39 anos';
        if ($idade < 50) return '40-49 anos';
        if ($idade < 60) return '50-59 anos';
        return '60+ anos';
    }

    /**
     * Obter questionário do usuário
     */
    public function obterQuestionarioUsuario(int $usuarioId): ?array
    {
        $questionario = $this->questionarioRepository->buscarPorUsuario($usuarioId);
        
        if (!$questionario) {
            return null;
        }
        
        return [
            'questionario' => $questionario,
            'analise_risco' => $this->calcularAnaliseRiscoProgressiva($questionario),
            'recomendacoes' => $this->gerarRecomendacoesProgressivas($questionario),
            'estatisticas_pessoais' => $this->calcularEstatisticasPessoais($questionario)
        ];
    }

    /**
     * Obter estatísticas gerais
     */
    public function obterEstatisticasGerais(): array
    {
        return $this->questionarioRepository->obterEstatisticas();
    }

    /**
     * Dashboard analítico de rastreamento
     */
    public function obterDashboardRastreamento(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $filtros);
        
        $totalQuestionarios = $query->count();
        
        // Estatísticas gerais
        $estatisticas = [
            'total_questionarios' => $totalQuestionarios,
            'distribuicao_sexo' => $this->obterDistribuicaoSexo($filtros),
            'distribuicao_idade' => $this->obterDistribuicaoIdade($filtros),
            'distribuicao_estado' => $this->obterDistribuicaoEstado($filtros),
            'fatores_risco' => $this->obterEstatisticasFatoresRisco($filtros),
            'elegibilidades' => $this->obterEstatisticasElegibilidade($filtros),
            'sinais_alerta' => $this->obterEstatisticasSinaisAlerta($filtros),
            'progresso_medio' => $this->obterProgressoMedio($filtros)
        ];
        
        return $estatisticas;
    }

    /**
     * Análise de fatores de risco
     */
    public function obterAnaliseFatoresRisco(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $questionarios = $query->get();
        
        $analise = [
            'tabagismo' => [
                'nunca_fumou' => $questionarios->where('status_tabagismo', 'Nunca')->count(),
                'ex_fumante' => $questionarios->where('status_tabagismo', 'Ex-fumante')->count(),
                'fumante_ativo' => $questionarios->where('status_tabagismo', 'Sim')->count(),
                'anos_medio_fumando' => $questionarios->where('anos_fumando', '>', 0)->avg('anos_fumando'),
                'macos_medio_dia' => $questionarios->where('macos_dia', '>', 0)->avg('macos_dia')
            ],
            'alcool' => [
                'consome' => $questionarios->where('consome_alcool', true)->count(),
                'nao_consome' => $questionarios->where('consome_alcool', false)->count(),
                'percentual_consome' => $questionarios->count() > 0 ? 
                    round(($questionarios->where('consome_alcool', true)->count() / $questionarios->count()) * 100, 2) : 0
            ],
            'atividade_fisica' => [
                'pratica' => $questionarios->where('pratica_atividade', true)->count(),
                'nao_pratica' => $questionarios->where('pratica_atividade', false)->count(),
                'percentual_pratica' => $questionarios->count() > 0 ? 
                    round(($questionarios->where('pratica_atividade', true)->count() / $questionarios->count()) * 100, 2) : 0
            ],
            'historico_familiar' => [
                'tem_historico' => $questionarios->where('parente_1grau_cancer', true)->count(),
                'nao_tem_historico' => $questionarios->where('parente_1grau_cancer', false)->count(),
                'tipos_cancer_familia' => $questionarios->whereNotNull('tipo_cancer_parente')
                    ->groupBy('tipo_cancer_parente')
                    ->map->count()
                    ->toArray()
            ],
            'imc' => [
                'abaixo_peso' => 0,
                'peso_normal' => 0,
                'sobrepeso' => 0,
                'obesidade' => 0,
                'imc_medio' => 0
            ]
        ];
        
        // Calcular estatísticas de IMC
        $questionariosComIMC = $questionarios->filter(function($q) {
            return $q->peso_kg && $q->altura_cm;
        });
        
        if ($questionariosComIMC->count() > 0) {
            $analise['imc']['imc_medio'] = round($questionariosComIMC->avg(function($q) {
                return $q->calcularIMC();
            }), 2);
            
            foreach ($questionariosComIMC as $questionario) {
                $categoria = $this->categorizarIMC($questionario->calcularIMC());
                switch ($categoria) {
                    case 'Abaixo do peso':
                        $analise['imc']['abaixo_peso']++;
                        break;
                    case 'Peso normal':
                        $analise['imc']['peso_normal']++;
                        break;
                    case 'Sobrepeso':
                        $analise['imc']['sobrepeso']++;
                        break;
                    case 'Obesidade':
                        $analise['imc']['obesidade']++;
                        break;
                }
            }
        }
        
        return $analise;
    }

    /**
     * Estatísticas de elegibilidade para rastreamentos
     */
    public function obterEstatisticasElegibilidade(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $questionarios = $query->get();
        
        $elegibilidades = [
            'cervical' => [
                'elegivel' => 0,
                'nao_elegivel' => 0,
                'sem_dados' => 0
            ],
            'mamografia' => [
                'elegivel' => 0,
                'nao_elegivel' => 0,
                'sem_dados' => 0
            ],
            'prostata' => [
                'elegivel' => 0,
                'nao_elegivel' => 0,
                'sem_dados' => 0
            ],
            'colorretal' => [
                'elegivel' => 0,
                'nao_elegivel' => 0,
                'sem_dados' => 0
            ]
        ];
        
        foreach ($questionarios as $questionario) {
            // Cervical
            if ($questionario->sexo_biologico && $questionario->data_nascimento) {
                if ($questionario->elegivelRastreamentoCervical()) {
                    $elegibilidades['cervical']['elegivel']++;
                } else {
                    $elegibilidades['cervical']['nao_elegivel']++;
                }
            } else {
                $elegibilidades['cervical']['sem_dados']++;
            }
            
            // Mamografia
            if ($questionario->sexo_biologico && $questionario->data_nascimento) {
                if ($questionario->elegivelMamografia()) {
                    $elegibilidades['mamografia']['elegivel']++;
                } else {
                    $elegibilidades['mamografia']['nao_elegivel']++;
                }
            } else {
                $elegibilidades['mamografia']['sem_dados']++;
            }
            
            // Próstata
            if ($questionario->sexo_biologico && $questionario->data_nascimento) {
                if ($questionario->elegivelRastreamentoProstata()) {
                    $elegibilidades['prostata']['elegivel']++;
                } else {
                    $elegibilidades['prostata']['nao_elegivel']++;
                }
            } else {
                $elegibilidades['prostata']['sem_dados']++;
            }
            
            // Colorretal
            if ($questionario->data_nascimento) {
                if ($questionario->elegivelRastreamentoColorretal()) {
                    $elegibilidades['colorretal']['elegivel']++;
                } else {
                    $elegibilidades['colorretal']['nao_elegivel']++;
                }
            } else {
                $elegibilidades['colorretal']['sem_dados']++;
            }
        }
        
        return $elegibilidades;
    }

    /**
     * Relatório de progresso dos questionários
     */
    public function obterRelatorioProgresso(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $questionarios = $query->get();
        
        $progresso = [
            'inicial' => 0,      // < 25%
            'basico' => 0,       // 25-49%
            'intermediario' => 0, // 50-74%
            'avancado' => 0,     // 75-99%
            'completo' => 0      // 100%
        ];
        
        $progressoDetalhado = [];
        
        foreach ($questionarios as $questionario) {
            $progressoQuestionario = $this->calcularProgressoQuestionario($questionario);
            $status = $progressoQuestionario['status'];
            
            $progresso[$status]++;
            
            $progressoDetalhado[] = [
                'usuario_id' => $questionario->usuario_id,
                'nome' => $questionario->nome_completo,
                'percentual' => $progressoQuestionario['percentual'],
                'status' => $status,
                'campos_preenchidos' => $progressoQuestionario['campos_preenchidos'],
                'campos_totais' => $progressoQuestionario['campos_totais'],
                'data_preenchimento' => $questionario->data_preenchimento
            ];
        }
        
        return [
            'distribuicao_progresso' => $progresso,
            'progresso_detalhado' => $progressoDetalhado,
            'progresso_medio' => $questionarios->count() > 0 ? 
                round($questionarios->avg(function($q) {
                    return $this->calcularProgressoQuestionario($q)['percentual'];
                }), 2) : 0
        ];
    }

    /**
     * Análise geográfica dos questionários
     */
    public function obterAnaliseGeografica(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $questionarios = $query->get();
        
        $analise = [
            'por_estado' => $questionarios->groupBy('estado')
                ->map(function($grupo) {
                    return [
                        'total' => $grupo->count(),
                        'percentual' => 0, // Será calculado depois
                        'fatores_risco' => [
                            'tabagismo' => $grupo->where('status_tabagismo', 'Sim')->count(),
                            'alcool' => $grupo->where('consome_alcool', true)->count(),
                            'sedentarismo' => $grupo->where('pratica_atividade', false)->count()
                        ]
                    ];
                })
                ->toArray(),
            'por_cidade' => $questionarios->groupBy('cidade')
                ->map(function($grupo) {
                    return [
                        'total' => $grupo->count(),
                        'estado' => $grupo->first()->estado ?? 'N/A'
                    ];
                })
                ->sortByDesc('total')
                ->take(20)
                ->toArray(),
            'regioes' => $this->agruparPorRegiao($questionarios)
        ];
        
        // Calcular percentuais por estado
        $total = $questionarios->count();
        foreach ($analise['por_estado'] as $estado => &$dados) {
            $dados['percentual'] = $total > 0 ? round(($dados['total'] / $total) * 100, 2) : 0;
        }
        
        return $analise;
    }

    /**
     * Tendências temporais dos questionários
     */
    public function obterTendenciasTemporais(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $agrupamento = $filtros['agrupamento'] ?? 'mes';
        
        $tendencias = [
            'por_periodo' => [],
            'crescimento' => 0,
            'periodo_maior_crescimento' => null,
            'periodo_menor_crescimento' => null
        ];
        
        switch ($agrupamento) {
            case 'dia':
                $tendencias['por_periodo'] = $query->selectRaw('DATE(data_preenchimento) as periodo, COUNT(*) as total')
                    ->groupBy('periodo')
                    ->orderBy('periodo')
                    ->get()
                    ->pluck('total', 'periodo')
                    ->toArray();
                break;
                
            case 'semana':
                $tendencias['por_periodo'] = $query->selectRaw('YEAR(data_preenchimento) as ano, WEEK(data_preenchimento) as semana, COUNT(*) as total')
                    ->groupBy('ano', 'semana')
                    ->orderBy('ano')
                    ->orderBy('semana')
                    ->get()
                    ->mapWithKeys(function($item) {
                        return ["{$item->ano}-W{$item->semana}" => $item->total];
                    })
                    ->toArray();
                break;
                
            case 'mes':
            default:
                $tendencias['por_periodo'] = $query->selectRaw('YEAR(data_preenchimento) as ano, MONTH(data_preenchimento) as mes, COUNT(*) as total')
                    ->groupBy('ano', 'mes')
                    ->orderBy('ano')
                    ->orderBy('mes')
                    ->get()
                    ->mapWithKeys(function($item) {
                        return ["{$item->ano}-{$item->mes}" => $item->total];
                    })
                    ->toArray();
                break;
        }
        
        // Calcular crescimento
        $valores = array_values($tendencias['por_periodo']);
        if (count($valores) > 1) {
            $primeiro = $valores[0];
            $ultimo = end($valores);
            $tendencias['crescimento'] = $primeiro > 0 ? round((($ultimo - $primeiro) / $primeiro) * 100, 2) : 0;
        }
        
        return $tendencias;
    }

    /**
     * Listar questionários com filtros
     */
    public function listarQuestionarios(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        
        // Aplicar filtros
        $this->aplicarFiltros($query, $filtros);
        
        // Paginação
        $perPage = $filtros['per_page'] ?? 15;
        $page = $filtros['page'] ?? 1;
        
        // Ordenação
        $sortBy = $filtros['sort_by'] ?? 'data_preenchimento';
        $sortDirection = $filtros['sort_direction'] ?? 'desc';
        
        $query->orderBy($sortBy, $sortDirection);
        
        $questionarios = $query->paginate($perPage, ['*'], 'page', $page);
        
        // Adicionar informações calculadas
        $questionarios->getCollection()->transform(function($questionario) {
            $progresso = $this->calcularProgressoQuestionario($questionario);
            
            return [
                'id' => $questionario->id,
                'usuario_id' => $questionario->usuario_id,
                'nome_completo' => $questionario->nome_completo,
                'data_preenchimento' => $questionario->data_preenchimento,
                'sexo_biologico' => $questionario->sexo_biologico,
                'idade' => $questionario->calcularIdade(),
                'estado' => $questionario->estado,
                'cidade' => $questionario->cidade,
                'progresso' => $progresso,
                'tem_sinais_alerta' => $questionario->temSinaisAlerta(),
                'status_tabagismo' => $questionario->status_tabagismo,
                'consome_alcool' => $questionario->consome_alcool,
                'pratica_atividade' => $questionario->pratica_atividade
            ];
        });
        
        return [
            'data' => $questionarios->items(),
            'pagination' => [
                'current_page' => $questionarios->currentPage(),
                'last_page' => $questionarios->lastPage(),
                'per_page' => $questionarios->perPage(),
                'total' => $questionarios->total(),
                'from' => $questionarios->firstItem(),
                'to' => $questionarios->lastItem()
            ]
        ];
    }

    /**
     * Aplicar filtros à query
     */
    private function aplicarFiltros($query, array $filtros): void
    {
        if (isset($filtros['sexo'])) {
            $query->where('sexo_biologico', $filtros['sexo']);
        }
        
        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        
        if (isset($filtros['cidade'])) {
            $query->where('cidade', 'like', '%' . $filtros['cidade'] . '%');
        }
        
        if (isset($filtros['status_tabagismo'])) {
            $query->where('status_tabagismo', $filtros['status_tabagismo']);
        }
        
        if (isset($filtros['tem_sinais_alerta'])) {
            if ($filtros['tem_sinais_alerta']) {
                $query->where(function($q) {
                    $q->where('sinais_alerta_intestino', true)
                      ->orWhere('sangramento_anormal', true)
                      ->orWhere('tosse_persistente', true)
                      ->orWhere('nodulos_palpaveis', true)
                      ->orWhere('perda_peso_nao_intencional', true);
                });
            } else {
                $query->where(function($q) {
                    $q->where('sinais_alerta_intestino', false)
                      ->where('sangramento_anormal', false)
                      ->where('tosse_persistente', false)
                      ->where('nodulos_palpaveis', false)
                      ->where('perda_peso_nao_intencional', false);
                });
            }
        }
        
        if (isset($filtros['data_inicio'])) {
            $query->where('data_preenchimento', '>=', $filtros['data_inicio']);
        }
        
        if (isset($filtros['data_fim'])) {
            $query->where('data_preenchimento', '<=', $filtros['data_fim']);
        }
        
        if (isset($filtros['faixa_etaria'])) {
            $this->aplicarFiltroFaixaEtaria($query, $filtros['faixa_etaria']);
        }
    }

    /**
     * Aplicar filtro de faixa etária
     */
    private function aplicarFiltroFaixaEtaria($query, string $faixaEtaria): void
    {
        switch ($faixaEtaria) {
            case '18-29':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 18 AND 29');
                break;
            case '30-39':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 30 AND 39');
                break;
            case '40-49':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 40 AND 49');
                break;
            case '50-59':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 50 AND 59');
                break;
            case '60+':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= 60');
                break;
        }
    }

    /**
     * Obter distribuição por sexo
     */
    private function obterDistribuicaoSexo(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        return $query->selectRaw('sexo_biologico, COUNT(*) as total')
            ->groupBy('sexo_biologico')
            ->pluck('total', 'sexo_biologico')
            ->toArray();
    }

    /**
     * Obter distribuição por idade
     */
    private function obterDistribuicaoIdade(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        return $query->selectRaw('
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 30 THEN "18-29"
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 40 THEN "30-39"
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 50 THEN "40-49"
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 60 THEN "50-59"
                    ELSE "60+"
                END as faixa_etaria,
                COUNT(*) as total
            ')
            ->groupBy('faixa_etaria')
            ->pluck('total', 'faixa_etaria')
            ->toArray();
    }

    /**
     * Obter distribuição por estado
     */
    private function obterDistribuicaoEstado(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        return $query->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'estado')
            ->toArray();
    }

    /**
     * Obter estatísticas de fatores de risco
     */
    private function obterEstatisticasFatoresRisco(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $questionarios = $query->get();
        
        return [
            'tabagismo_ativo' => $questionarios->where('status_tabagismo', 'Sim')->count(),
            'ex_fumante' => $questionarios->where('status_tabagismo', 'Ex-fumante')->count(),
            'consome_alcool' => $questionarios->where('consome_alcool', true)->count(),
            'sedentario' => $questionarios->where('pratica_atividade', false)->count(),
            'historico_familiar' => $questionarios->where('parente_1grau_cancer', true)->count()
        ];
    }

    /**
     * Obter estatísticas de sinais de alerta
     */
    private function obterEstatisticasSinaisAlerta(array $filtros = []): array
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $questionarios = $query->get();
        
        return [
            'total_com_sinais' => $questionarios->filter(function($q) {
                return $q->temSinaisAlerta();
            })->count(),
            'sinais_especificos' => [
                'sangramento_anormal' => $questionarios->where('sangramento_anormal', true)->count(),
                'tosse_persistente' => $questionarios->where('tosse_persistente', true)->count(),
                'nodulos_palpaveis' => $questionarios->where('nodulos_palpaveis', true)->count(),
                'perda_peso' => $questionarios->where('perda_peso_nao_intencional', true)->count(),
                'sinais_intestino' => $questionarios->where('sinais_alerta_intestino', true)->count()
            ]
        ];
    }

    /**
     * Obter progresso médio
     */
    private function obterProgressoMedio(array $filtros = []): float
    {
        $query = QuestionarioModel::query();
        $this->aplicarFiltros($query, $filtros);
        
        $questionarios = $query->get();
        
        if ($questionarios->isEmpty()) {
            return 0.0;
        }
        
        $progressoTotal = $questionarios->sum(function($questionario) {
            return $this->calcularProgressoQuestionario($questionario)['percentual'];
        });
        
        return round($progressoTotal / $questionarios->count(), 2);
    }

    /**
     * Agrupar questionários por região
     */
    private function agruparPorRegiao($questionarios): array
    {
        $regioes = [
            'Norte' => ['AC', 'AM', 'AP', 'PA', 'RO', 'RR', 'TO'],
            'Nordeste' => ['AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE'],
            'Centro-Oeste' => ['DF', 'GO', 'MT', 'MS'],
            'Sudeste' => ['ES', 'MG', 'RJ', 'SP'],
            'Sul' => ['PR', 'RS', 'SC']
        ];
        
        $agrupamento = [];
        
        foreach ($regioes as $regiao => $estados) {
            $agrupamento[$regiao] = $questionarios->whereIn('estado', $estados)->count();
        }
        
        return $agrupamento;
    }
}

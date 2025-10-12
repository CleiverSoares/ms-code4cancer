<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionarioModel;
use App\Services\ServicoQuestionarioService;
use App\Services\ServicoEmailAlertaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QuestionarioController extends Controller
{
    private ServicoQuestionarioService $servicoQuestionario;
    private ServicoEmailAlertaService $servicoEmailAlerta;

    public function __construct(ServicoQuestionarioService $servicoQuestionario, ServicoEmailAlertaService $servicoEmailAlerta)
    {
        $this->servicoQuestionario = $servicoQuestionario;
        $this->servicoEmailAlerta = $servicoEmailAlerta;
    }

    /**
     * Salvar questionário de rastreamento
     */
    public function salvarQuestionario(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if (!$usuario) {
                return response()->json(['erro' => 'Usuário não autenticado'], 401);
            }

            $dadosFrontend = $request->all();
            
            Log::info("📋 Dados recebidos do frontend para usuário ID: {$usuario->id}");
            Log::info("📋 Dados originais: " . json_encode($dadosFrontend));
            
            // Processar e converter dados do frontend
            $dadosProcessados = $this->processarDadosFrontend($dadosFrontend);
            
            $resultado = $this->servicoQuestionario->processarQuestionario($usuario->id, $dadosProcessados);
            
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Questionário salvo com sucesso',
                ...$resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar questionário: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }


    /**
     * Processar e converter dados do frontend
     */
    private function processarDadosFrontend(array $dados): array
    {
        $dadosProcessados = [];
        
        // Mapeamento de campos do frontend para backend
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
            'maisDe45Anos' => 'mais_de_45_anos',
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
            if (isset($dados[$frontend])) {
                $valor = $dados[$frontend];
                
                // Converter valores específicos
                if ($frontend === 'dataNascimento' && $valor) {
                    try {
                        // Aceitar formato YYYY-MM-DD e converter para Carbon
                        $dadosProcessados[$backend] = \Carbon\Carbon::createFromFormat('Y-m-d', $valor)->format('Y-m-d');
                        Log::info("📅 Data convertida: {$valor} → {$dadosProcessados[$backend]}");
                    } catch (\Exception $e) {
                        Log::warning("⚠️ Erro ao converter data: {$valor} - {$e->getMessage()}");
                        $dadosProcessados[$backend] = $valor; // Manter original se erro
                    }
                } else {
                    $dadosProcessados[$backend] = $valor;
                }
            }
        }

        Log::info("🔄 Dados processados: " . json_encode($dadosProcessados));
        return $dadosProcessados;
    }

    /**
     * Obter questionário do usuário
     */
    public function obterQuestionario(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if (!$usuario) {
                return response()->json(['erro' => 'Usuário não autenticado'], 401);
            }

            $resultado = $this->servicoQuestionario->obterQuestionarioUsuario($usuario->id);
            
            if (!$resultado) {
                return response()->json([
                    'sucesso' => true,
                    'questionario' => null,
                    'mensagem' => 'Nenhum questionário encontrado'
                ]);
            }

            return response()->json([
                'sucesso' => true,
                ...$resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter questionário: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter recomendações personalizadas
     */
    public function obterRecomendacoes(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if (!$usuario) {
                return response()->json(['erro' => 'Usuário não autenticado'], 401);
            }

            $resultado = $this->servicoQuestionario->obterQuestionarioUsuario($usuario->id);
            
            if (!$resultado) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Questionário não encontrado. Complete o questionário primeiro.'
                ], 404);
            }

            return response()->json([
                'sucesso' => true,
                'recomendacoes' => $resultado['recomendacoes'],
                'analise_risco' => $resultado['analise_risco']
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar recomendações: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter estatísticas gerais dos questionários
     */
    public function obterEstatisticas(): JsonResponse
    {
        try {
            $estatisticas = $this->servicoQuestionario->obterEstatisticasGerais();
            
            return response()->json([
                'sucesso' => true,
                'estatisticas' => $estatisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Dashboard analítico de rastreamento
     */
    public function dashboardRastreamento(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'sexo', 'faixa_etaria', 'estado', 'cidade', 
                'data_inicio', 'data_fim', 'status_tabagismo',
                'tem_sinais_alerta', 'elegivel_rastreamento'
            ]);

            $dashboard = $this->servicoQuestionario->obterDashboardRastreamento($filtros);
            
            return response()->json([
                'sucesso' => true,
                'dashboard' => $dashboard,
                'filtros_aplicados' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter dashboard de rastreamento: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Análise de fatores de risco
     */
    public function analiseFatoresRisco(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'sexo', 'faixa_etaria', 'estado', 'periodo'
            ]);

            $analise = $this->servicoQuestionario->obterAnaliseFatoresRisco($filtros);
            
            return response()->json([
                'sucesso' => true,
                'analise' => $analise,
                'filtros_aplicados' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter análise de fatores de risco: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Estatísticas de elegibilidade para rastreamentos
     */
    public function estatisticasElegibilidade(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'sexo', 'faixa_etaria', 'tipo_rastreamento'
            ]);

            $estatisticas = $this->servicoQuestionario->obterEstatisticasElegibilidade($filtros);
            
            return response()->json([
                'sucesso' => true,
                'estatisticas' => $estatisticas,
                'filtros_aplicados' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de elegibilidade: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Relatório de progresso dos questionários
     */
    public function relatorioProgresso(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'periodo', 'status_progresso', 'sexo'
            ]);

            $relatorio = $this->servicoQuestionario->obterRelatorioProgresso($filtros);
            
            return response()->json([
                'sucesso' => true,
                'relatorio' => $relatorio,
                'filtros_aplicados' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter relatório de progresso: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Análise geográfica dos questionários
     */
    public function analiseGeografica(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'estado', 'regiao', 'periodo'
            ]);

            $analise = $this->servicoQuestionario->obterAnaliseGeografica($filtros);
            
            return response()->json([
                'sucesso' => true,
                'analise' => $analise,
                'filtros_aplicados' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter análise geográfica: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Tendências temporais dos questionários
     */
    public function tendenciasTemporais(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'periodo', 'agrupamento', 'sexo', 'estado'
            ]);

            $tendencias = $this->servicoQuestionario->obterTendenciasTemporais($filtros);
            
            return response()->json([
                'sucesso' => true,
                'tendencias' => $tendencias,
                'filtros_aplicados' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter tendências temporais: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Lista de questionários com filtros
     */
    public function listarQuestionarios(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only([
                'sexo', 'faixa_etaria', 'estado', 'cidade',
                'data_inicio', 'data_fim', 'status_tabagismo',
                'tem_sinais_alerta', 'progresso_minimo',
                'page', 'per_page', 'sort_by', 'sort_direction'
            ]);

            $questionarios = $this->servicoQuestionario->listarQuestionarios($filtros);
            
            return response()->json([
                'sucesso' => true,
                'questionarios' => $questionarios,
                'filtros_aplicados' => $filtros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar questionários: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Testar sistema de alertas prioritários
     */
    public function testarAlertaPrioritario(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if (!$usuario) {
                return response()->json(['erro' => 'Usuário não autenticado'], 401);
            }

            // Criar dados de teste com alerta prioritário
            $dadosTeste = [
                'nomeCompleto' => 'Teste Usuário Prioritário',
                'dataNascimento' => '1980-05-15',
                'sexoBiologico' => 'M',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'precisaAtendimentoPrioritario' => true,
                'sangramentoAnormal' => true,
                'tossePersistente' => true,
                'nodulosPalpaveis' => true
            ];

            $resultado = $this->servicoQuestionario->processarQuestionario($usuario->id, $dadosTeste);
            
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Teste de alerta prioritário executado',
                'resultado' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao testar alerta prioritário: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor',
                'detalhes' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter estatísticas de alertas
     */
    public function estatisticasAlertas(): JsonResponse
    {
        try {
            $estatisticas = $this->servicoEmailAlerta->obterEstatisticasAlertas();
            
            return response()->json([
                'sucesso' => true,
                'estatisticas' => $estatisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de alertas: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor'
            ], 500);
        }
    }
}

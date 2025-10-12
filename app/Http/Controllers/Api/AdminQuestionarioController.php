<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionarioModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminQuestionarioController extends Controller
{
    /**
     * Listar questionários com filtros e paginação
     */
    public function listarQuestionarios(Request $request): JsonResponse
    {
        try {
            Log::info('=== LISTANDO QUESTIONÁRIOS PARA ADMINISTRAÇÃO ===');
            
            // Parâmetros de filtro
            $filtros = [
                'nome' => $request->get('nome'),
                'email' => $request->get('email'),
                'data_inicio' => $request->get('data_inicio'),
                'data_fim' => $request->get('data_fim'),
                'status' => $request->get('status'),
                'idade_min' => $request->get('idade_min'),
                'idade_max' => $request->get('idade_max'),
                'sexo' => $request->get('sexo'),
                'tipo_cancer' => $request->get('tipo_cancer'),
                'estagio' => $request->get('estagio')
            ];
            
            // Parâmetros de paginação
            $porPagina = $request->get('por_pagina', 15);
            $pagina = $request->get('pagina', 1);
            
            Log::info('Filtros aplicados:', $filtros);
            Log::info("Paginação: página {$pagina}, por página: {$porPagina}");
            
            // Construir query
            $query = QuestionarioModel::query();
            
            // Aplicar filtros
            if (!empty($filtros['nome'])) {
                $query->where('nome_completo', 'LIKE', '%' . $filtros['nome'] . '%');
            }
            
            if (!empty($filtros['data_inicio'])) {
                $query->whereDate('data_preenchimento', '>=', $filtros['data_inicio']);
            }
            
            if (!empty($filtros['data_fim'])) {
                $query->whereDate('data_preenchimento', '<=', $filtros['data_fim']);
            }
            
            if (!empty($filtros['idade_min'])) {
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= ?', [$filtros['idade_min']]);
            }
            
            if (!empty($filtros['idade_max'])) {
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) <= ?', [$filtros['idade_max']]);
            }
            
            if (!empty($filtros['sexo'])) {
                $query->where('sexo_biologico', $filtros['sexo']);
            }
            
            if (!empty($filtros['tipo_cancer'])) {
                $query->where('tipo_cancer_parente', 'LIKE', '%' . $filtros['tipo_cancer'] . '%');
            }
            
            // Filtro por prioridade
            if (isset($filtros['prioritario']) && $filtros['prioritario'] !== '') {
                $query->where('precisa_atendimento_prioritario', $filtros['prioritario']);
            }
            
            // Ordenar por prioridade (precisa_atendimento_prioritario primeiro) e depois por data
            $query->orderBy('precisa_atendimento_prioritario', 'desc')
                  ->orderBy('data_preenchimento', 'desc');
            
            // Executar query com paginação
            $questionarios = $query->paginate($porPagina, ['*'], 'pagina', $pagina);
            
            // Adicionar informações de prioridade para cada questionário
            $questionarios->getCollection()->transform(function ($questionario) {
                $questionario->prioridade_info = $this->calcularPrioridade($questionario);
                return $questionario;
            });
            
            // Estatísticas gerais
            $estatisticas = $this->obterEstatisticas($filtros);
            
            Log::info('Questionários encontrados: ' . $questionarios->total());
            
            return response()->json([
                'sucesso' => true,
                'dados' => [
                    'questionarios' => $questionarios->items(),
                    'paginacao' => [
                        'pagina_atual' => $questionarios->currentPage(),
                        'ultima_pagina' => $questionarios->lastPage(),
                        'total_registros' => $questionarios->total(),
                        'por_pagina' => $questionarios->perPage(),
                        'tem_proxima' => $questionarios->hasMorePages(),
                        'tem_anterior' => $questionarios->currentPage() > 1
                    ],
                    'estatisticas' => $estatisticas,
                    'filtros_aplicados' => array_filter($filtros)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erro ao listar questionários: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao listar questionários',
                'mensagem' => 'Tente novamente em alguns instantes'
            ], 500);
        }
    }
    
    /**
     * Obter detalhes completos de um questionário
     */
    public function obterDetalhesQuestionario(Request $request, int $id): JsonResponse
    {
        try {
            Log::info("=== OBTENDO DETALHES DO QUESTIONÁRIO ID: {$id} ===");
            
            $questionario = QuestionarioModel::find($id);
            
            if (!$questionario) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Questionário não encontrado'
                ], 404);
            }
            
            // Decodificar dados JSON se necessário
            $dadosCompletos = $questionario->toArray();
            
            if (isset($dadosCompletos['respostas']) && is_string($dadosCompletos['respostas'])) {
                $dadosCompletos['respostas'] = json_decode($dadosCompletos['respostas'], true);
            }
            
            if (isset($dadosCompletos['resultado_penai']) && is_string($dadosCompletos['resultado_penai'])) {
                $dadosCompletos['resultado_penai'] = json_decode($dadosCompletos['resultado_penai'], true);
            }
            
            Log::info('Detalhes obtidos com sucesso');
            
            return response()->json([
                'sucesso' => true,
                'dados' => $dadosCompletos
            ], 200);
            
        } catch (\Exception $e) {
            Log::error("Erro ao obter detalhes do questionário {$id}: " . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao obter detalhes do questionário'
            ], 500);
        }
    }
    
    /**
     * Calcular prioridade baseada no campo precisa_atendimento_prioritario
     */
    private function calcularPrioridade($questionario): array
    {
        try {
            $precisaAtendimentoPrioritario = $questionario->precisa_atendimento_prioritario;
            
            if ($precisaAtendimentoPrioritario) {
                return [
                    'nivel' => 'critica',
                    'label' => 'PRIORITÁRIO',
                    'cor' => 'danger',
                    'icone' => 'bi-exclamation-triangle-fill',
                    'descricao' => 'Atenção médica prioritária necessária',
                    'urgente' => true
                ];
            } else {
                return [
                    'nivel' => 'normal',
                    'label' => 'NORMAL',
                    'cor' => 'success',
                    'icone' => 'bi-check-circle-fill',
                    'descricao' => 'Acompanhamento médico padrão',
                    'urgente' => false
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao calcular prioridade: ' . $e->getMessage());
            
            return [
                'nivel' => 'erro',
                'label' => 'ERRO',
                'cor' => 'secondary',
                'icone' => 'bi-question-circle',
                'descricao' => 'Erro ao calcular prioridade',
                'urgente' => false
            ];
        }
    }

    /**
     * Obter estatísticas dos questionários
     */
    private function obterEstatisticas(array $filtros): array
    {
        try {
            $query = QuestionarioModel::query();
            
            // Aplicar filtros básicos para estatísticas
            if (!empty($filtros['data_inicio'])) {
                $query->whereDate('data_preenchimento', '>=', $filtros['data_inicio']);
            }
            
            if (!empty($filtros['data_fim'])) {
                $query->whereDate('data_preenchimento', '<=', $filtros['data_fim']);
            }
            
            $total = $query->count();
            
            // Contar casos prioritários e normais
            $prioritarios = $query->where('precisa_atendimento_prioritario', true)->count();
            $normais = $query->where('precisa_atendimento_prioritario', false)->count();
            
            // Estatísticas por sexo biológico
            $porSexo = QuestionarioModel::select('sexo_biologico', DB::raw('count(*) as total'))
                ->when(!empty($filtros['data_inicio']), function($q) use ($filtros) {
                    return $q->whereDate('data_preenchimento', '>=', $filtros['data_inicio']);
                })
                ->when(!empty($filtros['data_fim']), function($q) use ($filtros) {
                    return $q->whereDate('data_preenchimento', '<=', $filtros['data_fim']);
                })
                ->groupBy('sexo_biologico')
                ->get()
                ->pluck('total', 'sexo_biologico')
                ->toArray();
            
            // Estatísticas por faixa etária (calculada a partir da data de nascimento)
            $porFaixaEtaria = QuestionarioModel::select(
                    DB::raw('CASE 
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 18 THEN "Menor de 18"
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 18 AND 30 THEN "18-30"
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 31 AND 50 THEN "31-50"
                        WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 51 AND 70 THEN "51-70"
                        ELSE "Maior de 70"
                    END as faixa_etaria'),
                    DB::raw('count(*) as total')
                )
                ->whereNotNull('data_nascimento')
                ->when(!empty($filtros['data_inicio']), function($q) use ($filtros) {
                    return $q->whereDate('data_preenchimento', '>=', $filtros['data_inicio']);
                })
                ->when(!empty($filtros['data_fim']), function($q) use ($filtros) {
                    return $q->whereDate('data_preenchimento', '<=', $filtros['data_fim']);
                })
                ->groupBy('faixa_etaria')
                ->get()
                ->pluck('total', 'faixa_etaria')
                ->toArray();
            
            // Estatísticas por mês (últimos 12 meses)
            $porMes = QuestionarioModel::select(
                    DB::raw('DATE_FORMAT(data_preenchimento, "%Y-%m") as mes'),
                    DB::raw('count(*) as total')
                )
                ->where('data_preenchimento', '>=', now()->subMonths(12))
                ->groupBy('mes')
                ->orderBy('mes')
                ->get()
                ->pluck('total', 'mes')
                ->toArray();
            
            // Calcular idade média
            $idadeMedia = QuestionarioModel::selectRaw('AVG(TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE())) as idade_media')
                ->whereNotNull('data_nascimento')
                ->when(!empty($filtros['data_inicio']), function($q) use ($filtros) {
                    return $q->whereDate('data_preenchimento', '>=', $filtros['data_inicio']);
                })
                ->when(!empty($filtros['data_fim']), function($q) use ($filtros) {
                    return $q->whereDate('data_preenchimento', '<=', $filtros['data_fim']);
                })
                ->value('idade_media');

            return [
                'total_questionarios' => $total,
                'por_sexo' => $porSexo,
                'por_faixa_etaria' => $porFaixaEtaria,
                'por_mes' => $porMes,
                'prioritarios' => $prioritarios,
                'normais' => $normais,
                'idade_media' => round($idadeMedia ?? 0, 1),
                'ultimos_7_dias' => QuestionarioModel::where('data_preenchimento', '>=', now()->subDays(7))->count(),
                'ultimos_30_dias' => QuestionarioModel::where('data_preenchimento', '>=', now()->subDays(30))->count()
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao calcular estatísticas: ' . $e->getMessage());
            return [
                'total_questionarios' => 0,
                'por_sexo' => [],
                'por_faixa_etaria' => [],
                'por_mes' => [],
                'idade_media' => 0
            ];
        }
    }
    
    /**
     * Exportar questionários para CSV
     */
    public function exportarQuestionarios(Request $request): JsonResponse
    {
        try {
            Log::info('=== EXPORTANDO QUESTIONÁRIOS ===');
            
            // Aplicar mesmos filtros da listagem
            $filtros = [
                'nome' => $request->get('nome'),
                'data_inicio' => $request->get('data_inicio'),
                'data_fim' => $request->get('data_fim'),
                'idade_min' => $request->get('idade_min'),
                'idade_max' => $request->get('idade_max'),
                'sexo' => $request->get('sexo'),
                'tipo_cancer' => $request->get('tipo_cancer'),
                'prioritario' => $request->get('prioritario')
            ];
            
            Log::info('Filtros recebidos para exportação:', $filtros);
            
            $query = QuestionarioModel::query();
            
            // Aplicar filtros (mesma lógica da listagem)
            if (!empty($filtros['nome'])) {
                $query->where('nome_completo', 'LIKE', '%' . $filtros['nome'] . '%');
                Log::info('Filtro nome aplicado:', ['nome' => $filtros['nome']]);
            }
            
            if (!empty($filtros['data_inicio'])) {
                $query->whereDate('data_preenchimento', '>=', $filtros['data_inicio']);
                Log::info('Filtro data_inicio aplicado:', ['data_inicio' => $filtros['data_inicio']]);
            }
            
            if (!empty($filtros['data_fim'])) {
                $query->whereDate('data_preenchimento', '<=', $filtros['data_fim']);
                Log::info('Filtro data_fim aplicado:', ['data_fim' => $filtros['data_fim']]);
            }
            
            if (!empty($filtros['idade_min'])) {
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= ?', [$filtros['idade_min']]);
                Log::info('Filtro idade_min aplicado:', ['idade_min' => $filtros['idade_min']]);
            }
            
            if (!empty($filtros['idade_max'])) {
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) <= ?', [$filtros['idade_max']]);
                Log::info('Filtro idade_max aplicado:', ['idade_max' => $filtros['idade_max']]);
            }
            
            if (!empty($filtros['sexo'])) {
                $query->where('sexo_biologico', $filtros['sexo']);
                Log::info('Filtro sexo aplicado:', ['sexo' => $filtros['sexo']]);
            }
            
            if (!empty($filtros['tipo_cancer'])) {
                $query->where('tipo_cancer_parente', 'LIKE', '%' . $filtros['tipo_cancer'] . '%');
                Log::info('Filtro tipo_cancer aplicado:', ['tipo_cancer' => $filtros['tipo_cancer']]);
            }
            
            // Filtro por prioridade
            if (isset($filtros['prioritario']) && $filtros['prioritario'] !== '') {
                $query->where('precisa_atendimento_prioritario', $filtros['prioritario']);
                Log::info('Filtro prioritario aplicado:', ['prioritario' => $filtros['prioritario']]);
            }
            
            $questionarios = $query->orderBy('precisa_atendimento_prioritario', 'desc')
                                  ->orderBy('data_preenchimento', 'desc')
                                  ->get();
            
            Log::info('Questionários para exportação: ' . $questionarios->count());
            
            return response()->json([
                'sucesso' => true,
                'dados' => $questionarios->toArray(),
                'total_registros' => $questionarios->count(),
                'filtros_aplicados' => array_filter($filtros)
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erro ao exportar questionários: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao exportar questionários'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServicoOpenAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AnaliseIAController extends Controller
{
    private ServicoOpenAIService $servicoOpenAI;

    public function __construct(ServicoOpenAIService $servicoOpenAI)
    {
        $this->servicoOpenAI = $servicoOpenAI;
    }

    /**
     * Endpoint para testar conexão com GPT
     * GET /api/teste-conexao
     */
    public function testarConexao(): JsonResponse
    {
        $resultado = $this->servicoOpenAI->testarConexao();
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Endpoint para processar pergunta simples
     * POST /api/pergunta
     */
    public function processarPergunta(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pergunta' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $pergunta = $request->input('pergunta');
        $resultado = $this->servicoOpenAI->processarPergunta($pergunta);
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Endpoint para analisar questionário de paciente
     * POST /api/analisar-questionario
     */
    public function analisarQuestionario(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'respostas' => 'required|array',
            'respostas.*' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $respostas = $request->input('respostas');
        $resultado = $this->servicoOpenAI->analisarQuestionario($respostas);
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Endpoint para análise específica de qualidade de vida
     * POST /api/analise-qualidade-vida
     */
    public function analisarQualidadeVida(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dados_paciente' => 'required|array',
            'dados_paciente.nome' => 'required|string|max:255',
            'dados_paciente.idade' => 'required|integer|min:0|max:120',
            'dados_paciente.tipo_cancer' => 'required|string|max:255',
            'questionario' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $dadosPaciente = $request->input('dados_paciente');
        $questionario = $request->input('questionario');

        // Criar contexto específico para análise de qualidade de vida
        $contexto = [
            'paciente' => $dadosPaciente,
            'questionario' => $questionario,
            'data_analise' => now()->format('d/m/Y H:i')
        ];

        $resultado = $this->servicoOpenAI->analisarQuestionario($contexto);
        
        // Adicionar informações específicas do paciente
        $resultado['dados_paciente'] = $dadosPaciente;
        $resultado['tipo_analise'] = 'qualidade_vida';
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Endpoint para gerar insights personalizados
     * POST /api/gerar-insights
     */
    public function gerarInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'historico' => 'required|array',
            'historico.*.data' => 'required|date',
            'historico.*.respostas' => 'required|array',
            'tipo_insight' => 'required|string|in:tendencia,recomendacao,alerta'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $historico = $request->input('historico');
        $tipoInsight = $request->input('tipo_insight');

        // Criar prompt específico para insights
        $promptInsight = $this->criarPromptInsight($historico, $tipoInsight);
        
        try {
            $insight = $this->servicoOpenAI->processarPergunta($promptInsight);
            
            return response()->json([
                'sucesso' => true,
                'tipo_insight' => $tipoInsight,
                'insight' => $insight['resposta'],
                'historico_analisado' => count($historico),
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro ao gerar insights',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Cria prompt específico para geração de insights
     */
    private function criarPromptInsight(array $historico, string $tipoInsight): string
    {
        $historicoTexto = json_encode($historico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $instrucoes = match($tipoInsight) {
            'tendencia' => 'Analise as tendências ao longo do tempo e identifique padrões de melhora ou deterioração.',
            'recomendacao' => 'Forneça recomendações específicas baseadas no histórico do paciente.',
            'alerta' => 'Identifique sinais de alerta e situações que requerem atenção médica imediata.'
        };

        return "Com base no histórico de questionários de qualidade de vida de um paciente com câncer:

{$historicoTexto}

{$instrucoes}

Responda de forma clara e objetiva, focando em insights acionáveis para a equipe médica.";
    }

    /**
     * Endpoint para configurar modelo da IA
     * POST /api/configurar-modelo
     */
    public function configurarModelo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'modelo' => 'required|string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Modelo inválido',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $modelo = $request->input('modelo');
        $this->servicoOpenAI->definirModelo($modelo);

        return response()->json([
            'sucesso' => true,
            'modelo_configurado' => $modelo,
            'timestamp' => now()->toISOString()
        ]);
    }
}

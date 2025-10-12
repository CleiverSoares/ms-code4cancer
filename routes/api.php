<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnaliseIAController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\PenAIController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aqui estão registradas as rotas da API para o Code4Cancer.
| Essas rotas são carregadas pelo RouteServiceProvider dentro de um grupo
| que é atribuído ao middleware "api".
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ========================================
// ROTAS DO CHAT SOFIA
// ========================================

// Processar mensagem do chat
Route::post('/chat/mensagem', [ChatController::class, 'processarMensagem']);

// Analisar intenção da mensagem
Route::post('/chat/analisar-intencao', [ChatController::class, 'analisarIntencao']);

// Obter sugestões de próximos passos
Route::post('/chat/sugestoes', [ChatController::class, 'obterSugestoes']);

// Validar mensagem antes do envio
Route::post('/chat/validar-mensagem', [ChatController::class, 'validarMensagem']);

// Informações sobre a SOFIA
Route::get('/chat/info-sofia', [ChatController::class, 'obterInfoSofia']);

// Teste de conectividade do chat
Route::get('/chat/teste-conexao', [ChatController::class, 'testarConexaoChat']);

// ========================================
// ROTAS DE INTEGRAÇÃO COM IA (GPT)
// ========================================

// Teste de conexão com OpenAI
Route::get('/teste-conexao', [AnaliseIAController::class, 'testarConexao']);

// Processar pergunta simples
Route::post('/pergunta', [AnaliseIAController::class, 'processarPergunta']);

// Analisar questionário de paciente
Route::post('/analisar-questionario', [AnaliseIAController::class, 'analisarQuestionario']);

// Análise específica de qualidade de vida
Route::post('/analise-qualidade-vida', [AnaliseIAController::class, 'analisarQualidadeVida']);

// Gerar insights personalizados
Route::post('/gerar-insights', [AnaliseIAController::class, 'gerarInsights']);

// Configurar modelo da IA
Route::post('/configurar-modelo', [AnaliseIAController::class, 'configurarModelo']);

// ========================================
// ROTAS FUTURAS (AUTENTICAÇÃO)
// ========================================

// Grupo de rotas protegidas (implementar depois)
Route::middleware('auth:sanctum')->group(function () {
    // Rotas que requerem autenticação serão adicionadas aqui
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Dashboard protegido']);
    });
});

// ========================================
// ROTAS DO PEN AI ASSISTANT
// ========================================

// Iniciar nova conversa com Pen AI
Route::post('/pen-ai/iniciar-conversa', [PenAIController::class, 'iniciarConversa']);

// Enviar resposta do usuário e obter próxima pergunta
Route::post('/pen-ai/enviar-resposta', [PenAIController::class, 'enviarResposta']);

// Obter histórico da conversa
Route::get('/pen-ai/historico/{thread_id}', [PenAIController::class, 'obterHistorico']);

// Finalizar conversa
Route::post('/pen-ai/finalizar-conversa', [PenAIController::class, 'finalizarConversa']);

// Testar conexão com Pen AI
Route::get('/pen-ai/teste-conexao', [PenAIController::class, 'testarConexao']);

// Informações sobre o Pen AI
Route::get('/pen-ai/info', [PenAIController::class, 'obterInfo']);

// ========================================
// ROTAS DE SAÚDE DA API
// ========================================

Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'service' => 'Code4Cancer API'
    ]);
});

Route::get('/status', function () {
    return response()->json([
        'api' => 'Online',
        'database' => 'Connected',
        'openai' => 'Configured',
        'timestamp' => now()->toISOString()
    ]);
});

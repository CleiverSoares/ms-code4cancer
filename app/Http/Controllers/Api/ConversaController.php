<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServicoConversaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ConversaController extends Controller
{
    public function __construct(
        private ServicoConversaService $servicoConversa
    ) {}

    /**
     * Iniciar nova conversa
     */
    public function iniciarConversa(Request $request): JsonResponse
    {
        Log::info('=== INICIANDO CONVERSA VIA API ===');
        
        $usuario = $request->attributes->get('usuario_autenticado');
        $primeiraMensagem = $request->input('primeira_mensagem');
        
        Log::info('Usuário: ' . $usuario->email);
        
        try {
            $conversa = $this->servicoConversa->iniciarConversa($usuario->id, $primeiraMensagem);
            
            return response()->json([
                'sucesso' => true,
                'conversa_id' => $conversa->id,
                'status' => $conversa->status,
                'iniciada_em' => $conversa->iniciada_em,
                'mensagem' => 'Conversa iniciada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao iniciar conversa: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao iniciar conversa'
            ], 500);
        }
    }

    /**
     * Buscar conversas do usuário
     */
    public function buscarConversas(Request $request): JsonResponse
    {
        Log::info('=== BUSCANDO CONVERSAS VIA API ===');
        
        $usuario = $request->attributes->get('usuario_autenticado');
        $limite = $request->input('limite', 20);
        $status = $request->input('status'); // opcional: ativa, finalizada
        
        Log::info('Usuário: ' . $usuario->email);
        Log::info('Limite: ' . $limite);
        
        try {
            if ($status) {
                $conversas = $this->servicoConversa->buscarConversasPorStatus($usuario->id, $status);
            } else {
                $conversas = $this->servicoConversa->buscarConversasUsuario($usuario->id, $limite);
            }
            
            return response()->json([
                'sucesso' => true,
                'conversas' => $conversas,
                'total' => count($conversas)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar conversas: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao buscar conversas'
            ], 500);
        }
    }

    /**
     * Buscar conversa específica
     */
    public function buscarConversa(Request $request, int $conversaId): JsonResponse
    {
        Log::info('=== BUSCANDO CONVERSA ESPECÍFICA VIA API ===');
        Log::info('Conversa ID: ' . $conversaId);
        
        $usuario = $request->attributes->get('usuario_autenticado');
        
        try {
            $conversa = $this->servicoConversa->buscarConversa($conversaId, $usuario->id);
            
            if (!$conversa) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Conversa não encontrada'
                ], 404);
            }
            
            return response()->json([
                'sucesso' => true,
                'conversa' => $conversa
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar conversa: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao buscar conversa'
            ], 500);
        }
    }

    /**
     * Gerar resumo da conversa
     */
    public function gerarResumo(Request $request, int $conversaId): JsonResponse
    {
        Log::info('=== GERANDO RESUMO VIA API ===');
        Log::info('Conversa ID: ' . $conversaId);
        
        $usuario = $request->attributes->get('usuario_autenticado');
        
        try {
            // Verificar se a conversa pertence ao usuário
            $conversa = $this->servicoConversa->buscarConversa($conversaId, $usuario->id);
            
            if (!$conversa) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Conversa não encontrada'
                ], 404);
            }
            
            $resumo = $this->servicoConversa->gerarResumoConversa($conversaId);
            
            return response()->json([
                'sucesso' => true,
                'conversa_id' => $conversaId,
                'resumo' => $resumo
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao gerar resumo: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao gerar resumo'
            ], 500);
        }
    }

    /**
     * Finalizar conversa com resumo
     */
    public function finalizarConversa(Request $request, int $conversaId): JsonResponse
    {
        Log::info('=== FINALIZANDO CONVERSA VIA API ===');
        Log::info('Conversa ID: ' . $conversaId);
        
        $usuario = $request->attributes->get('usuario_autenticado');
        $tituloPersonalizado = $request->input('titulo');
        
        try {
            // Verificar se a conversa pertence ao usuário
            $conversa = $this->servicoConversa->buscarConversa($conversaId, $usuario->id);
            
            if (!$conversa) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Conversa não encontrada'
                ], 404);
            }
            
            $resultado = $this->servicoConversa->finalizarConversa($conversaId, $tituloPersonalizado);
            
            if ($resultado['sucesso']) {
                return response()->json($resultado);
            } else {
                return response()->json($resultado, 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao finalizar conversa: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao finalizar conversa'
            ], 500);
        }
    }

    /**
     * Obter estatísticas das conversas do usuário
     */
    public function obterEstatisticas(Request $request): JsonResponse
    {
        Log::info('=== OBTENDO ESTATÍSTICAS VIA API ===');
        
        $usuario = $request->attributes->get('usuario_autenticado');
        
        try {
            $estatisticas = $this->servicoConversa->obterEstatisticasUsuario($usuario->id);
            
            return response()->json([
                'sucesso' => true,
                'estatisticas' => $estatisticas
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao obter estatísticas'
            ], 500);
        }
    }

    /**
     * Deletar conversa
     */
    public function deletarConversa(Request $request, int $conversaId): JsonResponse
    {
        Log::info('=== DELETANDO CONVERSA VIA API ===');
        Log::info('Conversa ID: ' . $conversaId);
        
        $usuario = $request->attributes->get('usuario_autenticado');
        
        try {
            // Verificar se a conversa pertence ao usuário
            $conversa = $this->servicoConversa->buscarConversa($conversaId, $usuario->id);
            
            if (!$conversa) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Conversa não encontrada'
                ], 404);
            }
            
            $sucesso = $this->servicoConversa->deletarConversa($conversaId);
            
            if ($sucesso) {
                return response()->json([
                    'sucesso' => true,
                    'mensagem' => 'Conversa deletada com sucesso'
                ]);
            } else {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Erro ao deletar conversa'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao deletar conversa: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao deletar conversa'
            ], 500);
        }
    }
}

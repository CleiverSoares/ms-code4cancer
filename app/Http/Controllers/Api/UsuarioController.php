<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsuarioModel;
use App\Services\ServicoUsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function __construct(
        private ServicoUsuarioService $servicoUsuario
    ) {}
    /**
     * Sincronizar dados do usuário com Firebase
     * POST /api/usuario/sincronizar-firebase
     */
    public function sincronizarComFirebase(Request $request): JsonResponse
    {
        Log::info('=== SINCRONIZAÇÃO USUÁRIO FIREBASE ===');
        Log::info('Dados recebidos: ' . json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'firebase_data' => 'required|array',
            'firebase_data.users' => 'required|array|min:1',
            'firebase_data.users.0.localId' => 'required|string',
            'firebase_data.users.0.email' => 'required|email',
        ]);

        if ($validator->fails()) {
            Log::warning('Validação falhou: ' . json_encode($validator->errors()));
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados do Firebase inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        try {
            $usuario = $this->servicoUsuario->sincronizarComFirebase($request->input('firebase_data'));
            
            Log::info('Usuário sincronizado com sucesso: ' . $usuario->id);
            
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário sincronizado com sucesso',
                'usuario' => [
                    'id' => $usuario->id,
                    'firebase_uid' => $usuario->firebase_uid,
                    'email' => $usuario->email,
                    'nome' => $usuario->nome,
                    'foto_url' => $usuario->foto_url,
                    'provider' => $usuario->provider,
                    'email_verified' => $usuario->email_verified,
                    'ativo' => $usuario->ativo,
                    'criado_em' => $usuario->created_at,
                    'ultimo_login' => $usuario->ultimo_login_at
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar usuário: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao sincronizar usuário'
            ], 500);
        }
    }

    /**
     * Buscar usuário por Firebase UID
     * GET /api/usuario/buscar/{firebaseUid}
     */
    public function buscarPorFirebaseUid(string $firebaseUid): JsonResponse
    {
        Log::info('Buscando usuário por Firebase UID: ' . $firebaseUid);

        try {
            $usuario = $this->servicoUsuario->buscarPorFirebaseUid($firebaseUid);

            if (!$usuario) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Usuário não encontrado'
                ], 404);
            }

            return response()->json([
                'sucesso' => true,
                'usuario' => [
                    'id' => $usuario->id,
                    'firebase_uid' => $usuario->firebase_uid,
                    'email' => $usuario->email,
                    'nome' => $usuario->nome,
                    'foto_url' => $usuario->foto_url,
                    'provider' => $usuario->provider,
                    'email_verified' => $usuario->email_verified,
                    'ativo' => $usuario->ativo,
                    'criado_em' => $usuario->created_at,
                    'ultimo_login' => $usuario->ultimo_login_at
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar usuário: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao buscar usuário'
            ], 500);
        }
    }

    /**
     * Atualizar último login do usuário
     * PUT /api/usuario/{id}/atualizar-login
     */
    public function atualizarUltimoLogin(int $id): JsonResponse
    {
        Log::info('Atualizando último login para usuário: ' . $id);

        try {
            $usuario = UsuarioModel::find($id);

            if (!$usuario) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Usuário não encontrado'
                ], 404);
            }

            $usuario->atualizarUltimoLogin();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Último login atualizado com sucesso',
                'ultimo_login' => $usuario->ultimo_login_at
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar último login: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao atualizar último login'
            ], 500);
        }
    }

    /**
     * Atualizar preferências do usuário
     * PUT /api/usuario/{id}/preferencias
     */
    public function atualizarPreferencias(Request $request, int $id): JsonResponse
    {
        Log::info('Atualizando preferências para usuário: ' . $id);

        $validator = Validator::make($request->all(), [
            'preferencias' => 'required|array',
            'preferencias.tema' => 'sometimes|string|in:claro,escuro',
            'preferencias.notificacoes' => 'sometimes|boolean',
            'preferencias.idioma' => 'sometimes|string|in:pt-BR,en-US,es-ES',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados de preferências inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        try {
            $usuario = UsuarioModel::find($id);

            if (!$usuario) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Usuário não encontrado'
                ], 404);
            }

            $usuario->update([
                'preferencias' => $request->input('preferencias')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Preferências atualizadas com sucesso',
                'preferencias' => $usuario->preferencias
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar preferências: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao atualizar preferências'
            ], 500);
        }
    }

    /**
     * Desativar usuário
     * PUT /api/usuario/{id}/desativar
     */
    public function desativarUsuario(int $id): JsonResponse
    {
        Log::info('Desativando usuário: ' . $id);

        try {
            $usuario = UsuarioModel::find($id);

            if (!$usuario) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Usuário não encontrado'
                ], 404);
            }

            $usuario->update(['ativo' => false]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário desativado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao desativar usuário: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao desativar usuário'
            ], 500);
        }
    }

    /**
     * Busca o perfil do usuário autenticado.
     * Usado pelo middleware Firebase Auth.
     * GET /api/usuario/perfil
     */
    public function buscarPerfil(Request $request): JsonResponse
    {
        Log::info('Buscando perfil do usuário autenticado');
        
        $usuario = $request->get('usuario_autenticado');
        
        if (!$usuario) {
            return response()->json(['sucesso' => false, 'erro' => 'Usuário não autenticado'], 401);
        }

        return response()->json([
            'sucesso' => true,
            'usuario' => [
                'id' => $usuario->id,
                'firebase_uid' => $usuario->firebase_uid,
                'email' => $usuario->email,
                'nome' => $usuario->nome,
                'foto_url' => $usuario->foto_url,
                'provider' => $usuario->provider,
                'email_verified' => $usuario->email_verified,
                'ativo' => $usuario->ativo,
                'preferencias' => $usuario->preferencias,
                'ultimo_login_at' => $usuario->ultimo_login_at,
                'created_at' => $usuario->created_at
            ]
        ], 200);
    }
}

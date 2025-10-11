<?php

namespace App\Services;

use App\DTOs\DadosUsuarioFirebaseDTO;
use App\Models\UsuarioModel;
use App\Repositories\IUsuarioRepository;
use Illuminate\Support\Facades\Log;

class ServicoUsuarioService
{
    public function __construct(
        private IUsuarioRepository $usuarioRepository
    ) {}

    /**
     * Sincronizar usuário com dados do Firebase
     * Segue o princípio: se existe, só atualiza login; se não existe, cria
     */
    public function sincronizarComFirebase(array $dadosFirebase): UsuarioModel
    {
        Log::info('=== SINCRONIZAÇÃO USUÁRIO FIREBASE ===');
        
        $dadosUsuarioDTO = DadosUsuarioFirebaseDTO::criarAPartirDadosFirebase($dadosFirebase);
        
        $usuarioExistente = $this->usuarioRepository->buscarPorFirebaseUid($dadosUsuarioDTO->firebaseUid);
        
        if ($usuarioExistente) {
            Log::info('Usuário já existe, atualizando último login: ' . $usuarioExistente->id);
            $this->usuarioRepository->atualizarUltimoLogin($usuarioExistente);
            return $usuarioExistente;
        }
        
        Log::info('Usuário não existe, criando novo: ' . $dadosUsuarioDTO->email);
        $dadosParaCriacao = $dadosUsuarioDTO->paraArray();
        $novoUsuario = $this->usuarioRepository->criar($dadosParaCriacao);
        
        Log::info('Usuário criado com sucesso: ' . $novoUsuario->id);
        return $novoUsuario;
    }

    /**
     * Buscar usuário por Firebase UID
     */
    public function buscarPorFirebaseUid(string $firebaseUid): ?UsuarioModel
    {
        return $this->usuarioRepository->buscarPorFirebaseUid($firebaseUid);
    }

    /**
     * Buscar usuário por email
     */
    public function buscarPorEmail(string $email): ?UsuarioModel
    {
        return $this->usuarioRepository->buscarPorEmail($email);
    }

    /**
     * Verificar se usuário está ativo
     */
    public function usuarioEstaAtivo(UsuarioModel $usuario): bool
    {
        return $usuario->ativo;
    }

    /**
     * Atualizar preferências do usuário
     */
    public function atualizarPreferencias(UsuarioModel $usuario, array $preferencias): void
    {
        $usuario->update(['preferencias' => $preferencias]);
        Log::info('Preferências atualizadas para usuário: ' . $usuario->id);
    }

    /**
     * Desativar usuário
     */
    public function desativarUsuario(UsuarioModel $usuario): void
    {
        $usuario->update(['ativo' => false]);
        Log::info('Usuário desativado: ' . $usuario->id);
    }
}

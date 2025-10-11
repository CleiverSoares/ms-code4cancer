<?php

namespace App\Repositories;

use App\Models\UsuarioModel;

class UsuarioRepository implements IUsuarioRepository
{
    public function buscarPorFirebaseUid(string $firebaseUid): ?UsuarioModel
    {
        return UsuarioModel::where('firebase_uid', $firebaseUid)->first();
    }

    public function buscarPorEmail(string $email): ?UsuarioModel
    {
        return UsuarioModel::where('email', $email)->first();
    }

    public function criar(array $dadosUsuario): UsuarioModel
    {
        return UsuarioModel::create($dadosUsuario);
    }

    public function atualizarUltimoLogin(UsuarioModel $usuario): void
    {
        $usuario->update(['ultimo_login_at' => now()]);
    }
}

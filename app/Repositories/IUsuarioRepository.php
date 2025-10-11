<?php

namespace App\Repositories;

use App\Models\UsuarioModel;

interface IUsuarioRepository
{
    public function buscarPorFirebaseUid(string $firebaseUid): ?UsuarioModel;
    public function buscarPorEmail(string $email): ?UsuarioModel;
    public function criar(array $dadosUsuario): UsuarioModel;
    public function atualizarUltimoLogin(UsuarioModel $usuario): void;
}

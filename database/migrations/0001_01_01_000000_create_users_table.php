<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Dados do Firebase
            $table->string('firebase_uid')->unique()->comment('ID único do Firebase');
            $table->string('email')->unique()->comment('Email do usuário');
            $table->boolean('email_verified')->default(false)->comment('Email verificado');
            
            // Dados pessoais
            $table->string('nome')->nullable()->comment('Nome completo do usuário');
            $table->string('foto_url')->nullable()->comment('URL da foto de perfil');
            
            // Provider info
            $table->string('provider')->default('google.com')->comment('Provedor de autenticação');
            $table->string('provider_id')->nullable()->comment('ID do provedor (Google, Facebook, etc.)');
            
            // Timestamps do Firebase
            $table->timestamp('firebase_created_at')->nullable()->comment('Quando foi criado no Firebase');
            $table->timestamp('ultimo_login_at')->nullable()->comment('Último login');
            $table->timestamp('ultimo_refresh_at')->nullable()->comment('Último refresh do token');
            
            // Campos do sistema
            $table->boolean('ativo')->default(true)->comment('Usuário ativo');
            $table->json('preferencias')->nullable()->comment('Preferências do usuário');
            $table->text('observacoes')->nullable()->comment('Observações médicas');
            
            // Campos padrão do Laravel (opcionais para Firebase)
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            
            $table->timestamps();
            
            // Índices
            $table->index(['firebase_uid', 'email']);
            $table->index('provider');
            $table->index('ativo');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

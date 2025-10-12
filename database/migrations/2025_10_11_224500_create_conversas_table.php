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
        Schema::create('conversas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('titulo')->nullable(); // Título da conversa (ex: "Consulta sobre dor de cabeça")
            $table->text('resumo')->nullable(); // Resumo gerado pela IA
            $table->json('historico_mensagens')->nullable(); // Histórico completo das mensagens
            $table->json('metadados')->nullable(); // Metadados adicionais (intenções, palavras-chave, etc.)
            $table->string('status')->default('ativa'); // ativa, finalizada, arquivada
            $table->integer('total_mensagens')->default(0); // Contador de mensagens
            $table->integer('total_tokens_usados')->default(0); // Tokens consumidos na conversa
            $table->timestamp('iniciada_em')->useCurrent(); // Quando a conversa começou
            $table->timestamp('finalizada_em')->nullable(); // Quando foi finalizada
            $table->timestamp('ultima_mensagem_em')->nullable(); // Última mensagem enviada
            $table->timestamps();
            
            // Índices para performance
            $table->index(['usuario_id', 'status']);
            $table->index(['usuario_id', 'iniciada_em']);
            $table->index('finalizada_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversas');
    }
};

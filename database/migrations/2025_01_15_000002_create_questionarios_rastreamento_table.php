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
        Schema::create('questionarios_rastreamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->datetime('data_preenchimento')->useCurrent();
            
            // Dados pessoais básicos
            $table->string('nome_completo', 255)->nullable();
            $table->date('data_nascimento');
            $table->enum('sexo_biologico', ['F', 'M', 'O']);
            
            // Atividade sexual (essencial para rastreamento cervical)
            $table->boolean('atividade_sexual');
            
            // Dados físicos
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->integer('altura_cm')->nullable();
            
            // Localização
            $table->string('cidade', 100)->nullable();
            $table->char('estado', 2)->nullable();
            
            // Histórico pessoal de câncer
            $table->boolean('teve_cancer_pessoal')->nullable();
            
            // Histórico familiar
            $table->boolean('parente_1grau_cancer')->nullable();
            $table->string('tipo_cancer_parente', 255)->nullable();
            $table->integer('idade_diagnostico_parente')->nullable();
            
            // Tabagismo
            $table->enum('status_tabagismo', ['Nunca', 'Ex-fumante', 'Sim'])->nullable();
            $table->decimal('macos_dia', 4, 2)->nullable();
            $table->decimal('anos_fumando', 4, 1)->nullable();
            
            // Álcool e atividade física
            $table->boolean('consome_alcool')->nullable();
            $table->boolean('pratica_atividade')->nullable();
            
            // Perguntas específicas para mulheres
            $table->integer('idade_primeira_menstruacao')->nullable();
            $table->boolean('ja_engravidou')->nullable();
            $table->boolean('uso_anticoncepcional')->nullable();
            
            // Rastreamento cervical (mulheres)
            $table->enum('fez_papanicolau', ['Sim', 'Nao', 'Nao sei'])->nullable();
            $table->integer('ano_ultimo_papanicolau')->nullable();
            
            // Rastreamento mamário (mulheres)
            $table->enum('fez_mamografia', ['Sim', 'Nao', 'Nao sei'])->nullable();
            $table->integer('ano_ultima_mamografia')->nullable();
            $table->boolean('hist_fam_mama_ovario')->nullable();
            
            // Rastreamento prostático (homens)
            $table->boolean('fez_rastreamento_prostata')->nullable();
            $table->boolean('deseja_info_prostata')->nullable();
            
            // Idade (calculado mas armazenado)
            $table->boolean('mais_de_45_anos')->nullable();
            
            // Rastreamento colorretal
            $table->boolean('parente_1grau_colorretal')->nullable();
            $table->enum('fez_exame_colorretal', ['Sim', 'Nao', 'Nao sei'])->nullable();
            $table->integer('ano_ultimo_exame_colorretal')->nullable();
            
            // Sinais de alerta
            $table->boolean('sinais_alerta_intestino')->nullable();
            $table->boolean('sangramento_anormal')->nullable();
            $table->boolean('tosse_persistente')->nullable();
            $table->boolean('nodulos_palpaveis')->nullable();
            $table->boolean('perda_peso_nao_intencional')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['sexo_biologico', 'data_nascimento']);
            $table->index(['atividade_sexual', 'sexo_biologico']);
            $table->index('data_preenchimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionarios_rastreamento');
    }
};

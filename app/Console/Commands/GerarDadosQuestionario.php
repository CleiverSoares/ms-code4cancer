<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuestionarioModel;
use App\Models\UsuarioModel;
use Carbon\Carbon;
use Faker\Factory as Faker;

class GerarDadosQuestionario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questionario:gerar-dados {--quantidade=50 : Quantidade de questionários para gerar} {--limpar : Limpar dados existentes antes de gerar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera dados fictícios de questionários para análise e dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $quantidade = $this->option('quantidade');
        $limpar = $this->option('limpar');
        
        $this->info("🚀 Iniciando geração de {$quantidade} questionários...");
        
        if ($limpar) {
            $this->info("🧹 Limpando dados existentes...");
            QuestionarioModel::truncate();
            UsuarioModel::where('firebase_uid', 'like', 'ficticio_%')->delete();
        }
        
        $faker = Faker::create('pt_BR');
        
        // Obter usuários existentes ou criar alguns fictícios
        $usuarios = UsuarioModel::all();
        
        if ($usuarios->isEmpty()) {
            $this->info("👥 Criando usuários fictícios...");
            for ($i = 1; $i <= 20; $i++) {
                UsuarioModel::create([
                    'firebase_uid' => 'ficticio_' . $i,
                    'email' => "usuario{$i}@exemplo.com",
                    'nome' => $faker->name,
                    'foto_url' => null,
                    'email_verificado' => true,
                    'ultimo_login' => now()->subDays(rand(1, 30))
                ]);
            }
            $usuarios = UsuarioModel::all();
        }
        
        $this->info("📊 Gerando questionários...");
        $bar = $this->output->createProgressBar($quantidade);
        $bar->start();
        
        for ($i = 0; $i < $quantidade; $i++) {
            $usuario = $usuarios->random();
            $this->criarQuestionarioFicticio($usuario->id, $faker);
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $totalQuestionarios = QuestionarioModel::count();
        $totalUsuarios = UsuarioModel::count();
        
        $this->info("✅ Dados gerados com sucesso!");
        $this->info("📈 Total de usuários: {$totalUsuarios}");
        $this->info("📋 Total de questionários: {$totalQuestionarios}");
        $this->info("🎯 Dashboard pronto para análise!");
        
        return Command::SUCCESS;
    }
    
    private function criarQuestionarioFicticio(int $usuarioId, $faker): void
    {
        $sexo = $faker->randomElement(['F', 'M']);
        $dataNascimento = $faker->dateTimeBetween('-70 years', '-18 years');
        $idade = Carbon::parse($dataNascimento)->age;
        
        $questionario = [
            'usuario_id' => $usuarioId,
            'data_preenchimento' => $faker->dateTimeBetween('-6 months', 'now'),
            'nome_completo' => $faker->name,
            'data_nascimento' => $dataNascimento,
            'sexo_biologico' => $sexo,
            'atividade_sexual' => $faker->boolean(85),
            'peso_kg' => $faker->randomFloat(2, 45, 120),
            'altura_cm' => $faker->numberBetween(150, 200),
            'cidade' => $faker->city,
            'estado' => $faker->stateAbbr,
            'teve_cancer_pessoal' => $faker->boolean(5),
            'parente_1grau_cancer' => $faker->boolean(20),
            'tipo_cancer_parente' => $faker->boolean(20) ? $faker->randomElement(['Mama', 'Pulmão', 'Próstata', 'Colorretal', 'Cervical']) : null,
            'idade_diagnostico_parente' => $faker->boolean(20) ? $faker->numberBetween(30, 80) : null,
            'status_tabagismo' => $faker->randomElement(['Nunca', 'Ex-fumante', 'Sim']),
            'macos_dia' => $faker->randomElement(['Nunca', 'Ex-fumante', 'Sim']) !== 'Nunca' ? $faker->randomFloat(2, 0.5, 3) : null,
            'anos_fumando' => $faker->randomElement(['Nunca', 'Ex-fumante', 'Sim']) !== 'Nunca' ? $faker->randomFloat(1, 1, 30) : null,
            'consome_alcool' => $faker->boolean(60),
            'pratica_atividade' => $faker->boolean(70),
            'mais_de_45_anos' => $idade >= 45,
        ];
        
        // Dados específicos para mulheres
        if ($sexo === 'F') {
            $questionario = array_merge($questionario, [
                'idade_primeira_menstruacao' => $faker->numberBetween(10, 16),
                'ja_engravidou' => $faker->boolean(60),
                'uso_anticoncepcional' => $faker->boolean(40),
                'fez_papanicolau' => $faker->randomElement(['Sim', 'Nao', 'Nao sei']),
                'ano_ultimo_papanicolau' => $faker->randomElement(['Sim', 'Nao', 'Nao sei']) !== 'Nao' ? $faker->numberBetween(2018, 2024) : null,
                'fez_mamografia' => $idade >= 40 ? $faker->randomElement(['Sim', 'Nao', 'Nao sei']) : null,
                'ano_ultima_mamografia' => $idade >= 40 && $faker->randomElement(['Sim', 'Nao', 'Nao sei']) !== 'Nao' ? $faker->numberBetween(2018, 2024) : null,
                'hist_fam_mama_ovario' => $faker->boolean(15),
            ]);
        }
        
        // Dados específicos para homens
        if ($sexo === 'M') {
            $questionario = array_merge($questionario, [
                'fez_rastreamento_prostata' => $idade >= 50 ? $faker->boolean(60) : null,
                'deseja_info_prostata' => $idade >= 50 ? $faker->boolean(80) : null,
            ]);
        }
        
        // Dados gerais de rastreamento
        $questionario = array_merge($questionario, [
            'parente_1grau_colorretal' => $faker->boolean(10),
            'fez_exame_colorretal' => $idade >= 45 ? $faker->randomElement(['Sim', 'Nao', 'Nao sei']) : null,
            'ano_ultimo_exame_colorretal' => $idade >= 45 && $faker->randomElement(['Sim', 'Nao', 'Nao sei']) !== 'Nao' ? $faker->numberBetween(2018, 2024) : null,
            'sinais_alerta_intestino' => $faker->boolean(5),
            'sangramento_anormal' => $faker->boolean(3),
            'tosse_persistente' => $faker->boolean(8),
            'nodulos_palpaveis' => $faker->boolean(2),
            'perda_peso_nao_intencional' => $faker->boolean(4),
            'precisa_atendimento_prioritario' => $faker->boolean(3),
        ]);
        
        QuestionarioModel::create($questionario);
    }
}
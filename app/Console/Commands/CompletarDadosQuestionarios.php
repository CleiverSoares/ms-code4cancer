<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuestionarioModel;
use Illuminate\Support\Facades\Log;

class CompletarDadosQuestionarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questionarios:completar-dados {--force : Forçar atualização mesmo se dados já existirem}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completa dados faltantes em todos os questionários do banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando processo de completar dados dos questionários...');
        
        try {
            // Buscar todos os questionários
            $questionarios = QuestionarioModel::all();
            $total = $questionarios->count();
            
            if ($total === 0) {
                $this->warn('⚠️ Nenhum questionário encontrado no banco de dados.');
                return;
            }
            
            $this->info("📊 Total de questionários encontrados: {$total}");
            
            $bar = $this->output->createProgressBar($total);
            $bar->start();
            
            $atualizados = 0;
            $camposTotalAtualizados = 0;
            
            foreach ($questionarios as $questionario) {
                $camposAtualizados = $this->completarDadosQuestionario($questionario);
                
                if ($camposAtualizados > 0) {
                    $atualizados++;
                    $camposTotalAtualizados += $camposAtualizados;
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine(2);
            
            $this->info("✅ Processo concluído!");
            $this->info("📈 Estatísticas:");
            $this->info("   • Questionários atualizados: {$atualizados}/{$total}");
            $this->info("   • Total de campos atualizados: {$camposTotalAtualizados}");
            
            Log::info('Comando completar dados executado', [
                'total_questionarios' => $total,
                'questionarios_atualizados' => $atualizados,
                'campos_atualizados' => $camposTotalAtualizados
            ]);
            
        } catch (\Exception $e) {
            $this->error("❌ Erro durante o processo: " . $e->getMessage());
            Log::error('Erro no comando completar dados: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Completar dados de um questionário específico
     */
    private function completarDadosQuestionario(QuestionarioModel $questionario): int
    {
        $camposAtualizados = 0;
        $force = $this->option('force');
        
        // Dados para completar (valores padrão baseados no tipo de questionário)
        $dadosCompletos = [
            // Dados pessoais básicos
            'nome_completo' => $questionario->nome_completo ?: 'Nome não informado',
            'data_nascimento' => $questionario->data_nascimento ?: '1990-01-01',
            'sexo_biologico' => $questionario->sexo_biologico ?: 'O',
            'atividade_sexual' => $questionario->atividade_sexual ?? true,
            'peso_kg' => $questionario->peso_kg ?: 70.0,
            'altura_cm' => $questionario->altura_cm ?: 170,
            'cidade' => $questionario->cidade ?: 'São Paulo',
            'estado' => $questionario->estado ?: 'SP',
            
            // Histórico de câncer
            'teve_cancer_pessoal' => $questionario->teve_cancer_pessoal ?? false,
            'parente_1grau_cancer' => $questionario->parente_1grau_cancer ?? false,
            'tipo_cancer_parente' => $questionario->tipo_cancer_parente ?: 'Não informado',
            'idade_diagnostico_parente' => $questionario->idade_diagnostico_parente ?: 50,
            
            // Hábitos de vida
            'status_tabagismo' => $questionario->status_tabagismo ?: 'Nunca',
            'macos_dia' => $questionario->macos_dia ?: 0,
            'anos_fumando' => $questionario->anos_fumando ?: 0,
            'consome_alcool' => $questionario->consome_alcool ?? false,
            'pratica_atividade' => $questionario->pratica_atividade ?? true,
            
            // Dados específicos por sexo
            'idade_primeira_menstruacao' => $questionario->idade_primeira_menstruacao ?: 12,
            'ja_engravidou' => $questionario->ja_engravidou ?? false,
            'uso_anticoncepcional' => $questionario->uso_anticoncepcional ?? false,
            'fez_papanicolau' => $questionario->fez_papanicolau ?: 'Nao',
            'ano_ultimo_papanicolau' => $questionario->ano_ultimo_papanicolau ?: 2020,
            'fez_mamografia' => $questionario->fez_mamografia ?: 'Nao',
            'ano_ultima_mamografia' => $questionario->ano_ultima_mamografia ?: 2020,
            'hist_fam_mama_ovario' => $questionario->hist_fam_mama_ovario ?? false,
            'fez_rastreamento_prostata' => $questionario->fez_rastreamento_prostata ?? false,
            'deseja_info_prostata' => $questionario->deseja_info_prostata ?? true,
            
            // Rastreamento colorretal
            'mais_de_45_anos' => $questionario->mais_de_45_anos ?? true,
            'parente_1grau_colorretal' => $questionario->parente_1grau_colorretal ?? false,
            'fez_exame_colorretal' => $questionario->fez_exame_colorretal ?: 'Nao',
            'ano_ultimo_exame_colorretal' => $questionario->ano_ultimo_exame_colorretal ?: 2020,
            
            // Sinais de alerta
            'sinais_alerta_intestino' => $questionario->sinais_alerta_intestino ?? false,
            'sangramento_anormal' => $questionario->sangramento_anormal ?? false,
            'tosse_persistente' => $questionario->tosse_persistente ?? false,
            'nodulos_palpaveis' => $questionario->nodulos_palpaveis ?? false,
            'perda_peso_nao_intencional' => $questionario->perda_peso_nao_intencional ?? false,
            
            // Análise e prioridade
            'precisa_atendimento_prioritario' => $questionario->precisa_atendimento_prioritario ?? false,
            'resumo_ia' => $questionario->resumo_ia ?: 'Dados completados automaticamente pelo sistema administrativo.',
            'data_preenchimento' => $questionario->data_preenchimento ?: now()
        ];
        
        // Atualizar apenas campos que estão vazios ou nulos
        foreach ($dadosCompletos as $campo => $valor) {
            $deveAtualizar = $force || 
                           is_null($questionario->$campo) || 
                           $questionario->$campo === '' || 
                           $questionario->$campo === 0;
            
            if ($deveAtualizar) {
                $questionario->$campo = $valor;
                $camposAtualizados++;
            }
        }
        
        if ($camposAtualizados > 0) {
            $questionario->save();
        }
        
        return $camposAtualizados;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class QuestionarioModel extends Model
{
    use HasFactory;

    protected $table = 'questionarios_rastreamento';

    protected $fillable = [
        'usuario_id',
        'data_preenchimento',
        'nome_completo',
        'data_nascimento',
        'sexo_biologico',
        'atividade_sexual',
        'peso_kg',
        'altura_cm',
        'cidade',
        'estado',
        'teve_cancer_pessoal',
        'parente_1grau_cancer',
        'tipo_cancer_parente',
        'idade_diagnostico_parente',
        'status_tabagismo',
        'macos_dia',
        'anos_fumando',
        'consome_alcool',
        'pratica_atividade',
        'idade_primeira_menstruacao',
        'ja_engravidou',
        'uso_anticoncepcional',
        'fez_papanicolau',
        'ano_ultimo_papanicolau',
        'fez_mamografia',
        'ano_ultima_mamografia',
        'hist_fam_mama_ovario',
        'fez_rastreamento_prostata',
        'deseja_info_prostata',
        'mais_de_45_anos',
        'parente_1grau_colorretal',
        'fez_exame_colorretal',
        'ano_ultimo_exame_colorretal',
        'sinais_alerta_intestino',
        'sangramento_anormal',
        'tosse_persistente',
        'nodulos_palpaveis',
        'perda_peso_nao_intencional'
    ];

    protected function casts(): array
    {
        return [
            'data_preenchimento' => 'datetime',
            'data_nascimento' => 'date',
            'atividade_sexual' => 'boolean',
            'peso_kg' => 'decimal:2',
            'altura_cm' => 'integer',
            'teve_cancer_pessoal' => 'boolean',
            'parente_1grau_cancer' => 'boolean',
            'idade_diagnostico_parente' => 'integer',
            'macos_dia' => 'decimal:2',
            'anos_fumando' => 'decimal:1',
            'consome_alcool' => 'boolean',
            'pratica_atividade' => 'boolean',
            'idade_primeira_menstruacao' => 'integer',
            'ja_engravidou' => 'boolean',
            'uso_anticoncepcional' => 'boolean',
            'ano_ultimo_papanicolau' => 'integer',
            'ano_ultima_mamografia' => 'integer',
            'hist_fam_mama_ovario' => 'boolean',
            'fez_rastreamento_prostata' => 'boolean',
            'deseja_info_prostata' => 'boolean',
            'mais_de_45_anos' => 'boolean',
            'parente_1grau_colorretal' => 'boolean',
            'ano_ultimo_exame_colorretal' => 'integer',
            'sinais_alerta_intestino' => 'boolean',
            'sangramento_anormal' => 'boolean',
            'tosse_persistente' => 'boolean',
            'nodulos_palpaveis' => 'boolean',
            'perda_peso_nao_intencional' => 'boolean'
        ];
    }

    /**
     * Relacionamento com usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_id');
    }

    /**
     * Calcular idade atual
     */
    public function calcularIdade(): int
    {
        return Carbon::parse($this->data_nascimento)->age;
    }

    /**
     * Calcular IMC
     */
    public function calcularIMC(): ?float
    {
        if (!$this->peso_kg || !$this->altura_cm) {
            return null;
        }
        
        $alturaMetros = $this->altura_cm / 100;
        return round($this->peso_kg / ($alturaMetros * $alturaMetros), 2);
    }

    /**
     * Verificar se é elegível para rastreamento cervical
     */
    public function elegivelRastreamentoCervical(): bool
    {
        return $this->sexo_biologico === 'F' && 
               $this->atividade_sexual && 
               $this->calcularIdade() >= 21;
    }

    /**
     * Verificar se é elegível para mamografia
     */
    public function elegivelMamografia(): bool
    {
        return $this->sexo_biologico === 'F' && 
               $this->calcularIdade() >= 40;
    }

    /**
     * Verificar se é elegível para rastreamento prostático
     */
    public function elegivelRastreamentoProstata(): bool
    {
        return $this->sexo_biologico === 'M' && 
               $this->calcularIdade() >= 50;
    }

    /**
     * Verificar se é elegível para rastreamento colorretal
     */
    public function elegivelRastreamentoColorretal(): bool
    {
        return $this->calcularIdade() >= 45;
    }

    /**
     * Calcular risco geral de câncer
     */
    public function calcularRiscoGeral(): array
    {
        $risco = [
            'geral' => 'baixo',
            'fatores_risco' => [],
            'recomendacoes' => []
        ];

        $idade = $this->calcularIdade();

        // Fatores de risco
        if ($this->status_tabagismo === 'Sim') {
            $risco['fatores_risco'][] = 'Tabagismo ativo';
            $risco['geral'] = 'alto';
        } elseif ($this->status_tabagismo === 'Ex-fumante') {
            $risco['fatores_risco'][] = 'Histórico de tabagismo';
            $risco['geral'] = 'moderado';
        }

        if ($this->consome_alcool) {
            $risco['fatores_risco'][] = 'Consumo de álcool';
        }

        if ($this->parente_1grau_cancer) {
            $risco['fatores_risco'][] = 'Histórico familiar de câncer';
            $risco['geral'] = 'moderado';
        }

        if (!$this->pratica_atividade) {
            $risco['fatores_risco'][] = 'Sedentarismo';
        }

        // Recomendações baseadas na idade e sexo
        if ($this->elegivelRastreamentoCervical()) {
            $risco['recomendacoes'][] = 'Papanicolau a cada 3 anos';
        }

        if ($this->elegivelMamografia()) {
            $risco['recomendacoes'][] = 'Mamografia anual';
        }

        if ($this->elegivelRastreamentoProstata()) {
            $risco['recomendacoes'][] = 'Discussão sobre PSA';
        }

        if ($this->elegivelRastreamentoColorretal()) {
            $risco['recomendacoes'][] = 'Colonoscopia ou teste de sangue oculto';
        }

        return $risco;
    }

    /**
     * Verificar se há sinais de alerta
     */
    public function temSinaisAlerta(): bool
    {
        return $this->sangramento_anormal || 
               $this->tosse_persistente || 
               $this->nodulos_palpaveis || 
               $this->perda_peso_nao_intencional ||
               $this->sinais_alerta_intestino;
    }
}

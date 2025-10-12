<?php

namespace App\Repositories;

use App\Models\QuestionarioModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuestionarioRepository implements IQuestionarioRepository
{
    /**
     * Buscar questionário por ID do usuário
     */
    public function buscarPorUsuario(int $usuarioId): ?QuestionarioModel
    {
        return QuestionarioModel::where('usuario_id', $usuarioId)->first();
    }

    /**
     * Salvar ou atualizar questionário
     */
    public function salvar(array $dados): QuestionarioModel
    {
        $questionarioExistente = $this->buscarPorUsuario($dados['usuario_id']);
        
        if ($questionarioExistente) {
            $questionarioExistente->update($dados);
            return $questionarioExistente->fresh();
        }
        
        return QuestionarioModel::create($dados);
    }

    /**
     * Criar novo questionário
     */
    public function criar(array $dados): QuestionarioModel
    {
        return QuestionarioModel::create($dados);
    }

    /**
     * Atualizar questionário existente
     */
    public function atualizar(int $usuarioId, array $dados): bool
    {
        $questionario = $this->buscarPorUsuario($usuarioId);
        
        if (!$questionario) {
            return false;
        }
        
        return $questionario->update($dados);
    }

    /**
     * Verificar se usuário já possui questionário
     */
    public function usuarioPossuiQuestionario(int $usuarioId): bool
    {
        return QuestionarioModel::where('usuario_id', $usuarioId)->exists();
    }

    /**
     * Buscar questionários por critérios de risco
     */
    public function buscarPorFatoresRisco(array $criterios): Collection
    {
        $query = QuestionarioModel::query();
        
        if (isset($criterios['status_tabagismo'])) {
            $query->where('status_tabagismo', $criterios['status_tabagismo']);
        }
        
        if (isset($criterios['consome_alcool'])) {
            $query->where('consome_alcool', $criterios['consome_alcool']);
        }
        
        if (isset($criterios['parente_1grau_cancer'])) {
            $query->where('parente_1grau_cancer', $criterios['parente_1grau_cancer']);
        }
        
        if (isset($criterios['pratica_atividade'])) {
            $query->where('pratica_atividade', $criterios['pratica_atividade']);
        }
        
        if (isset($criterios['idade_minima'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= ?', [$criterios['idade_minima']]);
        }
        
        if (isset($criterios['idade_maxima'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) <= ?', [$criterios['idade_maxima']]);
        }
        
        return $query->with('usuario')->get();
    }

    /**
     * Buscar questionários com sinais de alerta
     */
    public function buscarComSinaisAlerta(): Collection
    {
        return QuestionarioModel::where(function($query) {
            $query->where('sangramento_anormal', true)
                  ->orWhere('tosse_persistente', true)
                  ->orWhere('nodulos_palpaveis', true)
                  ->orWhere('perda_peso_nao_intencional', true)
                  ->orWhere('sinais_alerta_intestino', true);
        })->with('usuario')->get();
    }

    /**
     * Buscar questionários elegíveis para rastreamento específico
     */
    public function buscarElegiveisParaRastreamento(string $tipoRastreamento): Collection
    {
        $query = QuestionarioModel::query();
        
        switch ($tipoRastreamento) {
            case 'cervical':
                $query->where('sexo_biologico', 'F')
                      ->where('atividade_sexual', true)
                      ->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= 21');
                break;
                
            case 'mamografia':
                $query->where('sexo_biologico', 'F')
                      ->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= 40');
                break;
                
            case 'prostata':
                $query->where('sexo_biologico', 'M')
                      ->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= 50');
                break;
                
            case 'colorretal':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) >= 45');
                break;
        }
        
        return $query->with('usuario')->get();
    }

    /**
     * Estatísticas gerais dos questionários
     */
    public function obterEstatisticas(): array
    {
        $total = QuestionarioModel::count();
        
        if ($total === 0) {
            return [
                'total_questionarios' => 0,
                'por_sexo' => [],
                'por_idade' => [],
                'fatores_risco' => [],
                'sinais_alerta' => 0,
                'elegibilidades' => []
            ];
        }
        
        $porSexo = QuestionarioModel::selectRaw('sexo_biologico, COUNT(*) as total')
            ->groupBy('sexo_biologico')
            ->pluck('total', 'sexo_biologico')
            ->toArray();
        
        $porIdade = QuestionarioModel::selectRaw('
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 30 THEN "18-29"
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 40 THEN "30-39"
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 50 THEN "40-49"
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 60 THEN "50-59"
                    ELSE "60+"
                END as faixa_etaria,
                COUNT(*) as total
            ')
            ->groupBy('faixa_etaria')
            ->pluck('total', 'faixa_etaria')
            ->toArray();
        
        $fatoresRisco = [
            'tabagismo_ativo' => QuestionarioModel::where('status_tabagismo', 'Sim')->count(),
            'ex_fumante' => QuestionarioModel::where('status_tabagismo', 'Ex-fumante')->count(),
            'consome_alcool' => QuestionarioModel::where('consome_alcool', true)->count(),
            'sedentario' => QuestionarioModel::where('pratica_atividade', false)->count(),
            'hist_familiar' => QuestionarioModel::where('parente_1grau_cancer', true)->count()
        ];
        
        $sinaisAlerta = QuestionarioModel::where(function($query) {
            $query->where('sangramento_anormal', true)
                  ->orWhere('tosse_persistente', true)
                  ->orWhere('nodulos_palpaveis', true)
                  ->orWhere('perda_peso_nao_intencional', true)
                  ->orWhere('sinais_alerta_intestino', true);
        })->count();
        
        $elegibilidades = [
            'cervical' => $this->buscarElegiveisParaRastreamento('cervical')->count(),
            'mamografia' => $this->buscarElegiveisParaRastreamento('mamografia')->count(),
            'prostata' => $this->buscarElegiveisParaRastreamento('prostata')->count(),
            'colorretal' => $this->buscarElegiveisParaRastreamento('colorretal')->count()
        ];
        
        return [
            'total_questionarios' => $total,
            'por_sexo' => $porSexo,
            'por_idade' => $porIdade,
            'fatores_risco' => $fatoresRisco,
            'sinais_alerta' => $sinaisAlerta,
            'elegibilidades' => $elegibilidades
        ];
    }

    /**
     * Deletar questionário
     */
    public function deletar(int $usuarioId): bool
    {
        $questionario = $this->buscarPorUsuario($usuarioId);
        
        if (!$questionario) {
            return false;
        }
        
        return $questionario->delete();
    }

    /**
     * Buscar questionários por período
     */
    public function buscarPorPeriodo(string $dataInicio, string $dataFim): Collection
    {
        return QuestionarioModel::whereBetween('data_preenchimento', [$dataInicio, $dataFim])
            ->with('usuario')
            ->get();
    }

    /**
     * Buscar questionários por localização
     */
    public function buscarPorLocalizacao(string $estado, ?string $cidade = null): Collection
    {
        $query = QuestionarioModel::where('estado', $estado);
        
        if ($cidade) {
            $query->where('cidade', 'LIKE', "%{$cidade}%");
        }
        
        return $query->with('usuario')->get();
    }

    /**
     * Buscar questionários com histórico familiar específico
     */
    public function buscarComHistoricoFamiliar(string $tipoCancer): Collection
    {
        return QuestionarioModel::where('parente_1grau_cancer', true)
            ->where('tipo_cancer_parente', 'LIKE', "%{$tipoCancer}%")
            ->with('usuario')
            ->get();
    }
}

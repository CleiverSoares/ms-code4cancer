<?php

namespace App\Services;

use App\Models\UsuarioModel;
use App\Models\UsuarioNivelModel;
use App\Models\AtividadeModel;
use App\Models\ConquistaModel;
use App\Models\DesafioModel;
use App\Models\UsuarioAtividadeModel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServicoGamificacaoService
{
    /**
     * Registrar atividade do usuário
     */
    public function registrarAtividade(int $usuarioId, string $codigoAtividade, array $dadosExtras = []): array
    {
        try {
            Log::info("🎮 Registrando atividade: {$codigoAtividade} para usuário ID: {$usuarioId}");
            
            // Buscar atividade
            $atividade = AtividadeModel::where('codigo', $codigoAtividade)->first();
            if (!$atividade) {
                Log::warning("Atividade não encontrada: {$codigoAtividade}");
                return [
                    'sucesso' => false,
                    'erro' => 'Atividade não encontrada'
                ];
            }

            // Calcular pontos
            $pontos = $atividade->calcularPontos($dadosExtras);
            $experiencia = $pontos; // 1 ponto = 1 XP

            // Registrar atividade
            UsuarioAtividadeModel::create([
                'usuario_id' => $usuarioId,
                'atividade_id' => $atividade->id,
                'pontos_ganhos' => $pontos,
                'experiencia_ganha' => $experiencia,
                'dados_extras' => $dadosExtras,
                'realizada_em' => now()
            ]);

            // Atualizar nível do usuário
            $nivelUsuario = $this->obterOuCriarNivelUsuario($usuarioId);
            $nivelUsuario->pontos_total += $pontos;
            $subiuNivel = $nivelUsuario->adicionarExperiencia($experiencia);
            $nivelUsuario->atualizarStreak();
            $nivelUsuario->save();

            // Verificar conquistas
            $novasConquistas = $this->verificarConquistas($usuarioId);

            Log::info("✅ Atividade registrada: {$pontos} pontos, {$experiencia} XP");

            return [
                'sucesso' => true,
                'pontos_ganhos' => $pontos,
                'experiencia_ganha' => $experiencia,
                'subiu_nivel' => $subiuNivel,
                'novo_nivel' => $nivelUsuario->nivel_atual,
                'novas_conquistas' => $novasConquistas
            ];

        } catch (\Exception $e) {
            Log::error("Erro ao registrar atividade: " . $e->getMessage());
            return [
                'sucesso' => false,
                'erro' => 'Erro interno'
            ];
        }
    }

    /**
     * Obter dashboard de gamificação
     */
    public function obterDashboard(int $usuarioId): array
    {
        try {
            Log::info("🎮 Obtendo dashboard para usuário ID: {$usuarioId}");
            
            $nivelUsuario = $this->obterOuCriarNivelUsuario($usuarioId);
            
            $estatisticas = [
                'nivel_atual' => $nivelUsuario->nivel_atual,
                'pontos_total' => $nivelUsuario->pontos_total,
                'experiencia_total' => $nivelUsuario->experiencia_total,
                'streak_dias' => $nivelUsuario->streak_dias,
                'progresso_proximo_nivel' => $nivelUsuario->progressoProximoNivel(),
                'experiencia_proximo_nivel' => $nivelUsuario->experienciaProximoNivel()
            ];

            $conquistas = ConquistaModel::where('ativa', true)->get();
            $desafios = DesafioModel::where('ativo', true)->get();
            $atividadesRecentes = UsuarioAtividadeModel::where('usuario_id', $usuarioId)
                ->with('atividade')
                ->orderBy('realizada_em', 'desc')
                ->limit(5)
                ->get();

            return [
                'dashboard' => [
                    'estatisticas' => $estatisticas,
                    'conquistas' => $conquistas,
                    'desafios' => $desafios,
                    'atividades_recentes' => $atividadesRecentes
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Erro ao obter dashboard: " . $e->getMessage());
            return [
                'dashboard' => [
                    'estatisticas' => [],
                    'conquistas' => [],
                    'desafios' => [],
                    'atividades_recentes' => []
                ]
            ];
        }
    }

    /**
     * Obter ou criar nível do usuário
     */
    public function obterOuCriarNivelUsuario(int $usuarioId): UsuarioNivelModel
    {
        $nivel = UsuarioNivelModel::where('usuario_id', $usuarioId)->first();
        
        if (!$nivel) {
            $nivel = UsuarioNivelModel::create([
                'usuario_id' => $usuarioId,
                'nivel_atual' => 1,
                'experiencia_total' => 0,
                'pontos_total' => 0,
                'streak_dias' => 0,
                'estatisticas' => []
            ]);
        }
        
        return $nivel;
    }

    /**
     * Verificar conquistas
     */
    private function verificarConquistas(int $usuarioId): array
    {
        // Implementação básica - pode ser expandida
        return [];
    }
}

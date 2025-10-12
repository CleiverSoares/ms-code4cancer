<?php

namespace App\Http\Controllers;

use App\Services\ServicoNoticiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NoticiaController extends Controller
{
    private ServicoNoticiaService $servicoNoticia;

    public function __construct(ServicoNoticiaService $servicoNoticia)
    {
        $this->servicoNoticia = $servicoNoticia;
    }

    /**
     * Listar notícias para o frontend
     */
    public function listar(Request $request): JsonResponse
    {
        try {
            $limite = $request->query('limite', 10);
            $limite = min($limite, 50); // Máximo 50 notícias por requisição

            $noticias = $this->servicoNoticia->obterNoticiasParaFrontend($limite);

            return response()->json([
                'sucesso' => true,
                'dados' => $noticias,
                'total' => count($noticias),
                'mensagem' => 'Notícias recuperadas com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar notícias', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro interno do servidor',
                'erro' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar e processar novas notícias
     */
    public function buscarNovas(Request $request): JsonResponse
    {
        try {
            $quantidade = $request->query('quantidade', 10);
            $quantidade = min($quantidade, 20); // Máximo 20 notícias por busca

            Log::info('Iniciando busca manual de notícias', ['quantidade' => $quantidade]);

            $noticiasProcessadas = $this->servicoNoticia->buscarEProcessarNoticias($quantidade);

            return response()->json([
                'sucesso' => true,
                'dados' => $noticiasProcessadas,
                'total_processadas' => count($noticiasProcessadas),
                'mensagem' => 'Busca de notícias concluída com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro na busca manual de notícias', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar notícias',
                'erro' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter estatísticas das notícias
     */
    public function estatisticas(): JsonResponse
    {
        try {
            $noticiasRecentes = $this->servicoNoticia->obterNoticiasParaFrontend(30);
            
            $estatisticas = [
                'total_noticias_recentes' => count($noticiasRecentes),
                'ultima_atualizacao' => count($noticiasRecentes) > 0 
                    ? $noticiasRecentes[0]['data_publicacao'] 
                    : null,
                'fontes_principais' => $this->obterFontesPrincipais($noticiasRecentes),
                'categorias_disponiveis' => ['cancer']
            ];

            return response()->json([
                'sucesso' => true,
                'dados' => $estatisticas,
                'mensagem' => 'Estatísticas recuperadas com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas', [
                'erro' => $e->getMessage()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao obter estatísticas',
                'erro' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpar notícias antigas
     */
    public function limparAntigas(Request $request): JsonResponse
    {
        try {
            $dias = $request->query('dias', 30);
            $dias = max($dias, 7); // Mínimo 7 dias

            $noticiasDesativadas = $this->servicoNoticia->limparNoticiasAntigas($dias);

            return response()->json([
                'sucesso' => true,
                'dados' => [
                    'noticias_desativadas' => $noticiasDesativadas,
                    'dias_considerados' => $dias
                ],
                'mensagem' => "Limpeza concluída. {$noticiasDesativadas} notícias desativadas."
            ]);

        } catch (\Exception $e) {
            Log::error('Erro na limpeza de notícias antigas', [
                'erro' => $e->getMessage()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao limpar notícias antigas',
                'erro' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter fontes principais das notícias
     */
    private function obterFontesPrincipais(array $noticias): array
    {
        $fontes = [];
        
        foreach ($noticias as $noticia) {
            $fonte = $noticia['fonte'];
            if (!isset($fontes[$fonte])) {
                $fontes[$fonte] = 0;
            }
            $fontes[$fonte]++;
        }

        arsort($fontes);
        return array_slice($fontes, 0, 5, true);
    }
}

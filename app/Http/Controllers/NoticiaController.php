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
     * Listar notícias para o frontend com paginação
     */
    public function listar(Request $request): JsonResponse
    {
        try {
            $porPagina = (int) $request->query('por_pagina', 10);
            $porPagina = min($porPagina, 50); // Máximo 50 notícias por página

            // Usar paginação nativa do Laravel
            $paginator = $this->servicoNoticia->obterNoticiasPaginadas($porPagina);

            // Transformar dados para o frontend
            $dados = $paginator->map(function ($noticia) {
                return [
                    'id' => $noticia->id,
                    'titulo' => $noticia->titulo,
                    'resumo' => $noticia->resumo,
                    'url' => $noticia->url,
                    'url_imagem' => $noticia->url_imagem,
                    'alt_imagem' => $noticia->alt_imagem,
                    'legenda_imagem' => $noticia->legenda_imagem,
                    'fonte' => $noticia->fonte,
                    'data_publicacao' => $noticia->data_publicacao->format('d/m/Y H:i'),
                    'categoria' => $noticia->categoria
                ];
            });

            return response()->json([
                'sucesso' => true,
                'dados' => $dados,
                'paginacao' => [
                    'pagina_atual' => $paginator->currentPage(),
                    'por_pagina' => $paginator->perPage(),
                    'total_registros' => $paginator->total(),
                    'total_paginas' => $paginator->lastPage(),
                    'tem_proxima' => $paginator->hasMorePages(),
                    'tem_anterior' => $paginator->currentPage() > 1,
                    'proxima_pagina' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                    'pagina_anterior' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
                    'links' => [
                        'primeira' => $paginator->url(1),
                        'ultima' => $paginator->url($paginator->lastPage()),
                        'proxima' => $paginator->nextPageUrl(),
                        'anterior' => $paginator->previousPageUrl()
                    ]
                ],
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

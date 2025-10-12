<?php

namespace App\Services;

use App\Repositories\INoticiaRepository;
use App\Services\ServicoOpenAIService;
use App\Services\ServicoBuscaNoticiasService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServicoNoticiaService
{
    private INoticiaRepository $noticiaRepository;
    private ServicoOpenAIService $openAIService;
    private ServicoBuscaNoticiasService $buscaNoticiasService;

    public function __construct(
        INoticiaRepository $noticiaRepository,
        ServicoOpenAIService $openAIService,
        ServicoBuscaNoticiasService $buscaNoticiasService
    ) {
        $this->noticiaRepository = $noticiaRepository;
        $this->openAIService = $openAIService;
        $this->buscaNoticiasService = $buscaNoticiasService;
    }

    /**
     * Buscar e processar notícias sobre câncer
     */
    public function buscarEProcessarNoticias(int $quantidade = 10): array
    {
        try {
            Log::info('Iniciando busca de notícias sobre câncer', ['quantidade' => $quantidade]);

            // Buscar notícias reais usando APIs e web search
            $noticiasBrutas = $this->buscaNoticiasService->buscarNoticiasReais($quantidade);
            
            $noticiasProcessadas = [];
            
            foreach ($noticiasBrutas as $noticiaBruta) {
                try {
                    $noticiaProcessada = $this->processarNoticia($noticiaBruta);
                    
                    if ($noticiaProcessada && !$this->noticiaRepository->existePorUrl($noticiaProcessada['url'])) {
                        $noticiaSalva = $this->noticiaRepository->criar($noticiaProcessada);
                        $noticiasProcessadas[] = $noticiaSalva;
                        
                        Log::info('Notícia salva com sucesso', [
                            'id' => $noticiaSalva->id, 
                            'titulo' => $noticiaSalva->titulo,
                            'url' => $noticiaSalva->url
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao processar notícia individual', [
                        'erro' => $e->getMessage(),
                        'noticia' => $noticiaBruta
                    ]);
                }
            }

            Log::info('Busca de notícias concluída', ['processadas' => count($noticiasProcessadas)]);
            
            return $noticiasProcessadas;

        } catch (\Exception $e) {
            Log::error('Erro na busca de notícias', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Processar uma notícia individual usando IA
     */
    private function processarNoticia(array $noticiaBruta): ?array
    {
        try {
            $prompt = $this->criarPromptResumo($noticiaBruta['conteudo']);
            $resumo = $this->openAIService->gerarResumo($prompt);

            return [
                'titulo' => $noticiaBruta['titulo'],
                'resumo' => $resumo,
                'url' => $noticiaBruta['url'],
                'url_imagem' => $noticiaBruta['url_imagem'] ?? null,
                'alt_imagem' => $noticiaBruta['alt_imagem'] ?? null,
                'legenda_imagem' => $noticiaBruta['legenda_imagem'] ?? null,
                'fonte' => $noticiaBruta['fonte'],
                'data_publicacao' => $noticiaBruta['data_publicacao'],
                'categoria' => 'cancer',
                'ativa' => true
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao processar notícia com IA', ['erro' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Criar prompt para resumo da notícia com personalidade da Sofia
     */
    private function criarPromptResumo(string $conteudo): string
    {
        return "Resuma esta notícia sobre câncer em máximo 80 palavras, de forma simples e direta, como uma notícia de jornal. " .
               "Foque nos pontos principais da pesquisa ou descoberta mencionada. " .
               "Seja conciso e objetivo.\n\n" .
               "Notícia: " . $conteudo;
    }


    /**
     * Obter notícias paginadas para o frontend
     */
    public function obterNoticiasPaginadas(int $porPagina = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->noticiaRepository->buscarComPaginacao($porPagina);
    }

    /**
     * Obter notícias para o frontend
     */
    public function obterNoticiasParaFrontend(int $limite = 10): array
    {
        $noticias = $this->noticiaRepository->buscarRecentes(30)
            ->take($limite);

        return $noticias->map(function ($noticia) {
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
        })->toArray();
    }

    /**
     * Limpar notícias antigas
     */
    public function limparNoticiasAntigas(int $dias = 30): int
    {
        return $this->noticiaRepository->desativarAntigas($dias);
    }
}

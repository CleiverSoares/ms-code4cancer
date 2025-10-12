<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ServicoOpenAIService;

class ServicoBuscaNoticiasService
{
    private array $fontesNoticias = [
        'newsapi' => [
            'url' => 'https://newsapi.org/v2/everything',
            'api_key' => null
        ],
        'web_search' => [
            'enabled' => true
        ]
    ];

    private ServicoOpenAIService $openAIService;

    public function __construct(ServicoOpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
        $this->fontesNoticias['newsapi']['api_key'] = config('services.newsapi.key', env('NEWSAPI_KEY'));
    }

    /**
     * Buscar notícias reais sobre câncer usando GPT
     */
    public function buscarNoticiasReais(int $quantidade = 10): array
    {
        Log::info('Iniciando busca de notícias reais sobre câncer', ['quantidade' => $quantidade]);

        // Buscar notícias reais de fontes confiáveis
        $noticiasEncontradas = $this->buscarNoticiasReaisWeb($quantidade);

        Log::info('Busca de notícias concluída', ['encontradas' => count($noticiasEncontradas)]);

        return $noticiasEncontradas;
    }

    /**
     * Buscar notícias reais de fontes web confiáveis
     */
    private function buscarNoticiasReaisWeb(int $quantidade): array
    {
        Log::info('🔍 Sofia está buscando notícias reais sobre câncer em fontes confiáveis...');
        
        $noticias = [];
        
        // 1. Buscar do INCA (Instituto Nacional de Câncer)
        $noticiasINCA = $this->buscarNoticiasINCA();
        $noticias = array_merge($noticias, $noticiasINCA);
        
        // 2. Buscar de RSS feeds de saúde
        $noticiasRSS = $this->buscarRSSFeeds($quantidade);
        $noticias = array_merge($noticias, $noticiasRSS);
        
        // Filtrar e validar notícias
        $noticiasValidas = [];
        foreach ($noticias as $noticia) {
            if ($this->validarNoticia($noticia) && $this->validarRelevanciaCancer($noticia['titulo'] . ' ' . $noticia['conteudo'])) {
                $noticiasValidas[] = $noticia;
            }
        }
        
        Log::info('Notícias encontradas e validadas', ['total' => count($noticiasValidas)]);
        
        return array_slice($noticiasValidas, 0, $quantidade);
    }

    /**
     * Buscar notícias do INCA (Instituto Nacional de Câncer)
     */
    private function buscarNoticiasINCA(): array
    {
        $noticias = [];
        
        try {
            Log::info('🔍 Sofia está buscando notícias do INCA sobre prevenção de câncer...');
            
            // Buscar página de notícias do INCA
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->get('https://www.inca.gov.br/noticias');
            
            if ($response->successful()) {
                $html = $response->body();
                
                // Extrair notícias usando regex simples
                preg_match_all('/<a[^>]*href="([^"]*)"[^>]*class="[^"]*noticia[^"]*"[^>]*>.*?<h3[^>]*>(.*?)<\/h3>.*?<p[^>]*>(.*?)<\/p>/s', $html, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $url = 'https://www.inca.gov.br' . $match[1];
                    $titulo = strip_tags($match[2]);
                    $resumo = strip_tags($match[3]);
                    
                    if ($this->validarRelevanciaCancer($titulo . ' ' . $resumo)) {
                        $noticias[] = [
                            'titulo' => $titulo,
                            'conteudo' => $resumo,
                            'url' => $url,
                            'url_imagem' => null,
                            'alt_imagem' => 'Imagem da notícia: ' . $titulo,
                            'legenda_imagem' => $resumo,
                            'fonte' => 'INCA',
                            'data_publicacao' => Carbon::now()
                        ];
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar notícias do INCA', ['erro' => $e->getMessage()]);
        }
        
        Log::info('Notícias encontradas no INCA', ['total' => count($noticias)]);
        return $noticias;
    }

    /**
     * Buscar notícias via NewsAPI
     */
    private function buscarViaNewsAPI(int $quantidade): array
    {
        try {
            Log::info('Buscando notícias via NewsAPI');

            $response = Http::timeout(30)->get($this->fontesNoticias['newsapi']['url'], [
                'q' => 'cancer OR oncologia OR tumor OR neoplasia',
                'language' => 'pt',
                'sortBy' => 'publishedAt',
                'pageSize' => min($quantidade, 100),
                'apiKey' => $this->fontesNoticias['newsapi']['api_key']
            ]);

            if ($response->failed()) {
                Log::error('Erro na NewsAPI', ['status' => $response->status(), 'body' => $response->body()]);
                return [];
            }

            $data = $response->json();
            $noticias = [];

            if (isset($data['articles'])) {
                foreach ($data['articles'] as $article) {
                    if ($this->validarNoticia($article)) {
                        $noticias[] = $this->processarNoticiaNewsAPI($article);
                    }
                }
            }

            Log::info('Notícias encontradas via NewsAPI', ['total' => count($noticias)]);
            return $noticias;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar via NewsAPI', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar notícias via web search REAL
     */
    private function buscarViaWebSearch(int $quantidade): array
    {
        Log::info('Buscando notícias via web search REAL', ['quantidade' => $quantidade]);

        try {
            // Buscar notícias reais usando web search
            $noticiasEncontradas = $this->buscarNoticiasReaisWeb($quantidade);
            
            Log::info('Notícias encontradas via web search', ['total' => count($noticiasEncontradas)]);
            
            // Se não encontrou notícias reais, retornar vazio
            if (empty($noticiasEncontradas)) {
                Log::info('Nenhuma notícia encontrada via web search');
                return [];
            }
            
            return $noticiasEncontradas;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar via web search', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar notícias usando GPT para encontrar conteúdo sobre câncer
     */
    private function buscarNoticiasViaGPT(int $quantidade): array
    {
        try {
            Log::info('🔍 Sofia está usando GPT para buscar notícias sobre câncer...');
            
            $prompt = "Com base no seu conhecimento sobre oncologia, crie {$quantidade} notícias realistas sobre câncer, prevenção, tratamentos ou pesquisas oncológicas. " .
                     "Para cada notícia, forneça EXATAMENTE no formato abaixo:\n\n" .
                     "Título: [título da notícia]\n" .
                     "Resumo: [resumo de 2-3 frases sobre a notícia]\n" .
                     "URL: [URL fictícia mas realista]\n" .
                     "URL Imagem: [deixe vazio]\n" .
                     "Fonte: [nome da fonte/jornal]\n" .
                     "Data: [data da publicação]\n" .
                     "---\n\n" .
                     "Crie notícias realistas sobre prevenção, detecção precoce, novos tratamentos ou pesquisas sobre câncer. " .
                     "Use URLs fictícias mas realistas de sites conhecidos como G1, UOL, Folha, Estadão, INCA, etc. " .
                     "IMPORTANTE: Deixe o campo 'URL Imagem' sempre vazio.";
            
            $respostaGPT = $this->openAIService->gerarResumo($prompt);
            
            // Processar resposta do GPT e extrair notícias
            return $this->processarRespostaGPT($respostaGPT, $quantidade);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar notícias via GPT', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Processar resposta do GPT e extrair notícias estruturadas
     */
    private function processarRespostaGPT(string $resposta, int $quantidade): array
    {
        $noticias = [];
        
        // Dividir por separador "---"
        $blocos = explode('---', $resposta);
        
        foreach ($blocos as $bloco) {
            $bloco = trim($bloco);
            if (empty($bloco)) continue;
            
            $noticia = $this->extrairNoticiaDoBloco($bloco);
            if ($noticia) {
                $noticias[] = $noticia;
            }
        }
        
        return array_slice($noticias, 0, $quantidade);
    }

    /**
     * Extrair dados da notícia de um bloco de texto
     */
    private function extrairNoticiaDoBloco(string $bloco): ?array
    {
        $linhas = explode("\n", $bloco);
        $noticia = [];
        
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if (empty($linha)) continue;
            
            if (preg_match('/^Título[:\s]*(.+)/i', $linha, $matches)) {
                $noticia['titulo'] = trim($matches[1]);
            } elseif (preg_match('/^Resumo[:\s]*(.+)/i', $linha, $matches)) {
                $noticia['conteudo'] = trim($matches[1]);
            } elseif (preg_match('/^URL[:\s]*(.+)/i', $linha, $matches)) {
                $noticia['url'] = trim($matches[1]);
            } elseif (preg_match('/^URL Imagem[:\s]*(.+)/i', $linha, $matches)) {
                $urlImagem = trim($matches[1]);
                // Só aceitar se não for placeholder ou vazio
                if (!empty($urlImagem) && !str_contains($urlImagem, 'placeholder') && !str_contains($urlImagem, 'via.placeholder')) {
                    $noticia['url_imagem'] = $urlImagem;
                }
            } elseif (preg_match('/^Fonte[:\s]*(.+)/i', $linha, $matches)) {
                $noticia['fonte'] = trim($matches[1]);
            } elseif (preg_match('/^Data[:\s]*(.+)/i', $linha, $matches)) {
                try {
                    $noticia['data_publicacao'] = Carbon::parse(trim($matches[1]));
                } catch (\Exception $e) {
                    $noticia['data_publicacao'] = Carbon::now();
                }
            }
        }
        
        // Validar se tem os campos obrigatórios
        if (isset($noticia['titulo']) && isset($noticia['url'])) {
            return $this->finalizarNoticia($noticia);
        }
        
        return null;
    }

    /**
     * Finalizar estrutura da notícia
     */
    private function finalizarNoticia(array $noticia): array
    {
        return [
            'titulo' => $noticia['titulo'] ?? 'Notícia sobre câncer',
            'conteudo' => $noticia['conteudo'] ?? $noticia['titulo'] ?? 'Informações sobre câncer',
            'url' => $noticia['url'] ?? 'https://exemplo.com/noticia-cancer',
            'url_imagem' => $noticia['url_imagem'] ?? null,
            'alt_imagem' => 'Imagem da notícia: ' . ($noticia['titulo'] ?? 'Câncer'),
            'legenda_imagem' => $noticia['conteudo'] ?? '',
            'fonte' => $noticia['fonte'] ?? 'GPT Search',
            'data_publicacao' => $noticia['data_publicacao'] ?? Carbon::now()
        ];
    }

    /**
     * Buscar notícias em RSS feeds de saúde focando em prevenção de câncer
     */
    private function buscarRSSFeeds(int $quantidade): array
    {
        $noticias = [];
        
        Log::info('🔍 Sofia está buscando notícias sobre PREVENÇÃO DE CÂNCER e cuidados oncológicos...');
        
        // RSS feeds de saúde brasileiros especializados em oncologia
        $rssFeeds = [
            'https://g1.globo.com/rss/g1/saude/',
            'https://www.uol.com.br/feeds/saude.xml',
            'https://www.folha.uol.com.br/rss/saude.xml',
            'https://www.estadao.com.br/rss/saude.xml',
            'https://www.inca.gov.br/rss/noticias',
            'https://www.sboc.org.br/rss',
            'https://www.oncoguia.org.br/rss'
        ];
        
        foreach ($rssFeeds as $feedUrl) {
            try {
                Log::info('Buscando RSS feed', ['url' => $feedUrl]);
                
                $rssContent = Http::timeout(10)->withOptions([
                    'verify' => false,
                ])->get($feedUrl);
                
                if ($rssContent->successful()) {
                    $xml = simplexml_load_string($rssContent->body());
                    
                    if ($xml && isset($xml->channel->item)) {
                        foreach ($xml->channel->item as $item) {
                            $titulo = (string) $item->title;
                            $descricao = (string) $item->description;
                            
                            if ($this->validarRelevanciaCancer($titulo . ' ' . $descricao)) {
                                $noticias[] = [
                                    'titulo' => $titulo,
                                    'conteudo' => $descricao,
                                    'url' => (string) $item->link,
                                    'url_imagem' => $this->extrairImagemRSS($item),
                                    'alt_imagem' => 'Imagem da notícia: ' . $titulo,
                                    'legenda_imagem' => substr(strip_tags($descricao), 0, 500), // Limitar tamanho
                                    'fonte' => $this->extrairFonteRSS($feedUrl),
                                    'data_publicacao' => Carbon::parse((string) $item->pubDate)
                                ];
                                
                                if (count($noticias) >= $quantidade) break 2;
                            }
                        }
                    }
                }
                
            } catch (\Exception $e) {
                Log::error('Erro ao buscar RSS feed', ['url' => $feedUrl, 'erro' => $e->getMessage()]);
            }
        }
        
        Log::info('Notícias encontradas em RSS feeds', ['total' => count($noticias)]);
        return $noticias;
    }

    /**
     * Executar busca web real usando web search
     */
    private function executarBuscaWeb(string $termo): array
    {
        try {
            Log::info('Executando busca web real', ['termo' => $termo]);
            
            // Usar web search para encontrar notícias reais
            $resultados = $this->buscarNoticiasWebSearch($termo);
            
            Log::info('Resultados da busca web', ['total' => count($resultados)]);
            return $resultados;
            
        } catch (\Exception $e) {
            Log::error('Erro na busca web', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar notícias usando web search real
     */
    private function buscarNoticiasWebSearch(string $termo): array
    {
        $noticias = [];
        
        // Termos de busca específicos para câncer
        $termosBusca = [
            $termo . ' site:g1.globo.com',
            $termo . ' site:uol.com.br',
            $termo . ' site:folha.uol.com.br',
            $termo . ' site:estadao.com.br',
            $termo . ' site:inca.gov.br',
            $termo . ' site:bbc.com',
            $termo . ' site:reuters.com'
        ];
        
        foreach ($termosBusca as $termoCompleto) {
            try {
                $resultados = $this->executarWebSearch($termoCompleto);
                $noticias = array_merge($noticias, $resultados);
                
                // Limitar para não sobrecarregar
                if (count($noticias) >= 10) break;
                
            } catch (\Exception $e) {
                Log::error('Erro na busca específica', ['termo' => $termoCompleto, 'erro' => $e->getMessage()]);
            }
        }
        
        return $noticias;
    }

    /**
     * Executar web search usando DuckDuckGo (gratuito)
     */
    private function executarWebSearch(string $termo): array
    {
        try {
            Log::info('Executando busca DuckDuckGo', ['termo' => $termo]);
            
            // Usar DuckDuckGo Instant Answer API (gratuita)
            $url = 'https://api.duckduckgo.com/?q=' . urlencode($termo) . '&format=json&no_html=1&skip_disambig=1';
            
            $response = Http::timeout(15)->withOptions([
                'verify' => false, // Desabilitar verificação SSL em desenvolvimento
            ])->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                return $this->processarResultadosDuckDuckGo($data, $termo);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro na busca DuckDuckGo', ['erro' => $e->getMessage()]);
        }
        
        return [];
    }

    /**
     * Processar resultados do DuckDuckGo
     */
    private function processarResultadosDuckDuckGo(array $data, string $termo): array
    {
        $noticias = [];
        
        // Processar resultados relacionados
        if (isset($data['RelatedTopics']) && is_array($data['RelatedTopics'])) {
            foreach ($data['RelatedTopics'] as $topico) {
                if (isset($topico['Text']) && isset($topico['FirstURL'])) {
                    $titulo = $this->extrairTitulo($topico['Text']);
                    $conteudo = $topico['Text'];
                    $url = $topico['FirstURL'];
                    
                    if ($this->validarRelevanciaCancer($titulo . ' ' . $conteudo)) {
                        $noticias[] = [
                            'titulo' => $titulo,
                            'conteudo' => $conteudo,
                            'url' => $url,
                            'url_imagem' => null,
                            'alt_imagem' => 'Imagem da notícia: ' . $titulo,
                            'legenda_imagem' => $conteudo,
                            'fonte' => $this->extrairFonteURL($url),
                            'data_publicacao' => Carbon::now()
                        ];
                    }
                }
            }
        }
        
        // Processar abstract se disponível
        if (isset($data['Abstract']) && isset($data['AbstractURL'])) {
            $titulo = $data['Heading'] ?? 'Notícia sobre ' . $termo;
            $conteudo = $data['Abstract'];
            $url = $data['AbstractURL'];
            
            if ($this->validarRelevanciaCancer($titulo . ' ' . $conteudo)) {
                $noticias[] = [
                    'titulo' => $titulo,
                    'conteudo' => $conteudo,
                    'url' => $url,
                    'url_imagem' => $data['Image'] ?? null,
                    'alt_imagem' => 'Imagem da notícia: ' . $titulo,
                    'legenda_imagem' => $conteudo,
                    'fonte' => $this->extrairFonteURL($url),
                    'data_publicacao' => Carbon::now()
                ];
            }
        }
        
        return $noticias;
    }

    /**
     * Extrair título do texto
     */
    private function extrairTitulo(string $texto): string
    {
        // Pegar primeira linha ou até 100 caracteres
        $linhas = explode("\n", $texto);
        $titulo = trim($linhas[0]);
        
        if (strlen($titulo) > 100) {
            $titulo = substr($titulo, 0, 97) . '...';
        }
        
        return $titulo;
    }

    /**
     * Extrair fonte da URL
     */
    private function extrairFonteURL(string $url): string
    {
        $domain = parse_url($url, PHP_URL_HOST);
        
        $fontes = [
            'g1.globo.com' => 'G1 - Globo',
            'uol.com.br' => 'UOL',
            'folha.uol.com.br' => 'Folha de S.Paulo',
            'estadao.com.br' => 'Estadão',
            'inca.gov.br' => 'INCA',
            'bbc.com' => 'BBC Brasil',
            'reuters.com' => 'Reuters',
            'nature.com' => 'Nature',
            'cancer.gov' => 'National Cancer Institute'
        ];
        
        foreach ($fontes as $dominio => $fonte) {
            if (strpos($domain, $dominio) !== false) {
                return $fonte;
            }
        }
        
        return ucfirst($domain);
    }

    /**
     * Validar se o conteúdo é relevante para prevenção de câncer
     */
    private function validarRelevanciaCancer(string $texto): bool
    {
        // Palavras-chave OBRIGATÓRIAS sobre câncer (mais específicas)
        $palavrasCancerObrigatorias = [
            'câncer', 'cancer', 'oncologia', 'tumor', 'neoplasia',
            'quimioterapia', 'radioterapia', 'imunoterapia', 'metástase',
            'melanoma', 'leucemia', 'linfoma', 'carcinoma', 'adenocarcinoma',
            'câncer de', 'cancer de', 'tumor de', 'neoplasia de',
            'câncer de mama', 'cancer de mama', 'câncer de próstata', 'cancer de prostata',
            'câncer de pulmão', 'cancer de pulmao', 'câncer de fígado', 'cancer de figado',
            'câncer de cólon', 'cancer de colon', 'câncer cervical', 'cancer cervical',
            'câncer de pele', 'cancer de pele', 'câncer de estômago', 'cancer de estomago',
            'câncer de pâncreas', 'cancer de pancreas', 'psa'
        ];
        
        // Palavras-chave de prevenção (opcional, mas desejável)
        $palavrasPrevencao = [
            'prevenção', 'prevencao', 'prevenir', 'preventivo', 'preventiva',
            'rastreamento', 'screening', 'detecção precoce', 'deteccao precoce',
            'exame preventivo', 'check-up', 'checkup', 'diagnóstico precoce',
            'diagnostico precoce', 'cuidados', 'tratamento', 'terapia'
        ];
        
        $textoLower = strtolower($texto);
        
        // OBRIGATÓRIO: Deve mencionar câncer especificamente
        $temCancer = false;
        foreach ($palavrasCancerObrigatorias as $palavra) {
            if (strpos($textoLower, $palavra) !== false) {
                $temCancer = true;
                Log::info('🎯 Sofia encontrou palavra-chave de câncer', ['palavra' => $palavra]);
                break;
            }
        }
        
        if (!$temCancer) {
            Log::info('❌ Sofia: Conteúdo não menciona câncer especificamente', ['texto' => substr($texto, 0, 100)]);
            return false;
        }
        
        // Verificar se também tem palavras de prevenção (preferível)
        $temPrevencao = false;
        foreach ($palavrasPrevencao as $palavra) {
            if (strpos($textoLower, $palavra) !== false) {
                $temPrevencao = true;
                break;
            }
        }
        
        if ($temPrevencao) {
            Log::info('🎯 Sofia encontrou notícia sobre PREVENÇÃO DE CÂNCER!', [
                'texto' => substr($texto, 0, 100)
            ]);
        } else {
            Log::info('📰 Sofia encontrou notícia sobre câncer', [
                'texto' => substr($texto, 0, 100)
            ]);
        }
        
        return true;
    }

    /**
     * Extrair imagem do RSS
     */
    private function extrairImagemRSS($item): ?string
    {
        // Tentar diferentes campos de imagem
        if (isset($item->enclosure) && (string) $item->enclosure['type'] === 'image/jpeg') {
            return (string) $item->enclosure['url'];
        }
        
        if (isset($item->image)) {
            return (string) $item->image;
        }
        
        // Tentar extrair de CDATA
        $description = (string) $item->description;
        if (preg_match('/<img[^>]+src="([^"]+)"/', $description, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Extrair fonte do URL do RSS
     */
    private function extrairFonteRSS(string $feedUrl): string
    {
        if (strpos($feedUrl, 'g1.globo.com') !== false) return 'G1 - Globo';
        if (strpos($feedUrl, 'uol.com.br') !== false) return 'UOL';
        if (strpos($feedUrl, 'folha.uol.com.br') !== false) return 'Folha de S.Paulo';
        if (strpos($feedUrl, 'estadao.com.br') !== false) return 'Estadão';
        if (strpos($feedUrl, 'inca.gov.br') !== false) return 'INCA';
        
        return 'Fonte RSS';
    }


    /**
     * Validar se a notícia é relevante
     */
    private function validarNoticia(array $article): bool
    {
        $titulo = strtolower($article['titulo'] ?? $article['title'] ?? '');
        $descricao = strtolower($article['conteudo'] ?? $article['description'] ?? '');

        $palavrasChave = [
            'cancer', 'câncer', 'oncologia', 'tumor', 'neoplasia',
            'quimioterapia', 'radioterapia', 'imunoterapia', 'metástase'
        ];

        foreach ($palavrasChave as $palavra) {
            if (strpos($titulo, $palavra) !== false || strpos($descricao, $palavra) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Processar notícia da NewsAPI
     */
    private function processarNoticiaNewsAPI(array $article): array
    {
        return [
            'titulo' => $article['title'] ?? 'Título não disponível',
            'conteudo' => $article['description'] ?? $article['content'] ?? '',
            'url' => $article['url'] ?? '',
            'url_imagem' => $article['urlToImage'] ?? null,
            'alt_imagem' => 'Imagem da notícia: ' . ($article['title'] ?? ''),
            'legenda_imagem' => $article['description'] ?? null,
            'fonte' => $article['source']['name'] ?? 'Fonte não identificada',
            'data_publicacao' => isset($article['publishedAt']) 
                ? Carbon::parse($article['publishedAt']) 
                : Carbon::now()
        ];
    }
}

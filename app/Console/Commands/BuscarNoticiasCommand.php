<?php

namespace App\Console\Commands;

use App\Services\ServicoNoticiaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BuscarNoticiasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'noticias:buscar {--quantidade=10 : Quantidade de notícias para buscar} {--limpar-antigas : Limpar notícias antigas após busca}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca e processa notícias sobre câncer automaticamente';

    private ServicoNoticiaService $servicoNoticia;

    public function __construct(ServicoNoticiaService $servicoNoticia)
    {
        parent::__construct();
        $this->servicoNoticia = $servicoNoticia;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Sofia está iniciando sua busca por notícias sobre PREVENÇÃO DE CÂNCER...');
        
        $quantidade = (int) $this->option('quantidade');
        $limparAntigas = $this->option('limpar-antigas');

        try {
            // Buscar e processar notícias
            $this->info("📰 Sofia está buscando {$quantidade} notícias sobre prevenção e cuidados oncológicos...");
            
            $noticiasProcessadas = $this->servicoNoticia->buscarEProcessarNoticias($quantidade);
            
            $totalProcessadas = count($noticiasProcessadas);
            
            if ($totalProcessadas > 0) {
                $this->info("✅ Sofia processou {$totalProcessadas} notícias sobre prevenção de câncer!");
                
                // Mostrar resumo das notícias
                $this->table(
                    ['ID', 'Título', 'Fonte', 'Data'],
                    collect($noticiasProcessadas)->map(function ($noticia) {
                        return [
                            $noticia->id,
                            substr($noticia->titulo, 0, 50) . '...',
                            $noticia->fonte,
                            $noticia->data_publicacao->format('d/m/Y')
                        ];
                    })
                );
            } else {
                $this->warn('⚠️  Sofia não encontrou notícias novas sobre prevenção de câncer.');
            }

            // Limpar notícias antigas se solicitado
            if ($limparAntigas) {
                $this->info('🧹 Sofia está limpando notícias antigas...');
                $noticiasDesativadas = $this->servicoNoticia->limparNoticiasAntigas(30);
                $this->info("🗑️  Sofia removeu {$noticiasDesativadas} notícias antigas.");
            }

            // Log da execução
            Log::info('Comando de busca de notícias executado com sucesso', [
                'quantidade_solicitada' => $quantidade,
                'quantidade_processada' => $totalProcessadas,
                'limpeza_executada' => $limparAntigas
            ]);

            $this->info('🎉 Sofia concluiu sua busca com sucesso!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Sofia encontrou um erro: ' . $e->getMessage());
            
            Log::error('Erro no comando de busca de notícias', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar Repository e Service para injeção de dependência
        $this->app->bind(
            \App\Repositories\IUsuarioRepository::class,
            \App\Repositories\UsuarioRepository::class
        );

        $this->app->bind(
            \App\Repositories\IConversaRepository::class,
            \App\Repositories\ConversaRepository::class
        );

        $this->app->bind(
            \App\Repositories\INoticiaRepository::class,
            \App\Repositories\NoticiaRepository::class
        );

        // Registrar Repository e Service de Questionário
        $this->app->bind(
            \App\Repositories\IQuestionarioRepository::class,
            \App\Repositories\QuestionarioRepository::class
        );

        $this->app->bind(
            \App\Services\ServicoQuestionarioService::class,
            \App\Services\ServicoQuestionarioService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

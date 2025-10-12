<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// AGENDAMENTO DE TAREFAS AUTOMÁTICAS
// ========================================

// Buscar notícias sobre câncer diariamente às 08:00
Schedule::command('noticias:buscar --quantidade=10')
    ->dailyAt('08:00')
    ->name('buscar-noticias-diarias')
    ->description('Busca automática de notícias sobre câncer')
    ->withoutOverlapping()
    ->runInBackground();

// Limpar notícias antigas semanalmente aos domingos às 02:00
Schedule::command('noticias:buscar --quantidade=5 --limpar-antigas')
    ->weeklyOn(0, '02:00')
    ->name('limpeza-semanal-noticias')
    ->description('Limpeza semanal de notícias antigas')
    ->withoutOverlapping()
    ->runInBackground();

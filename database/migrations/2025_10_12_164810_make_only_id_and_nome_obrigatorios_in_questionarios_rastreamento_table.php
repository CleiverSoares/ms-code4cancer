<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questionarios_rastreamento', function (Blueprint $table) {
            // Tornar data_preenchimento nullable e com valor padrão automático
            $table->datetime('data_preenchimento')->nullable()->useCurrent()->change();
            
            // Manter apenas nome_completo como obrigatório (além do id que já é obrigatório)
            // Todos os outros campos já são nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionarios_rastreamento', function (Blueprint $table) {
            // Reverter data_preenchimento para obrigatório
            $table->datetime('data_preenchimento')->nullable(false)->useCurrent()->change();
        });
    }
};
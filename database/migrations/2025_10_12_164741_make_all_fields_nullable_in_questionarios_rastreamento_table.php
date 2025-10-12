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
            // Tornar todos os campos nullable, exceto usuario_id e data_preenchimento
            $table->enum('sexo_biologico', ['F', 'M', 'O'])->nullable()->change();
            $table->boolean('atividade_sexual')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionarios_rastreamento', function (Blueprint $table) {
            // Reverter para campos obrigatórios
            $table->enum('sexo_biologico', ['F', 'M', 'O'])->nullable(false)->change();
            $table->boolean('atividade_sexual')->nullable(false)->change();
        });
    }
};
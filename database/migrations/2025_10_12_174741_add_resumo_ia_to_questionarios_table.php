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
            $table->text('resumo_ia')->nullable()->after('precisa_atendimento_prioritario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionarios_rastreamento', function (Blueprint $table) {
            $table->dropColumn('resumo_ia');
        });
    }
};

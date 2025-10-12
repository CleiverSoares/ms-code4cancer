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
            $table->boolean('precisa_atendimento_prioritario')->default(false)->after('perda_peso_nao_intencional');
            $table->timestamp('email_alerta_enviado')->nullable()->after('precisa_atendimento_prioritario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionarios_rastreamento', function (Blueprint $table) {
            $table->dropColumn(['precisa_atendimento_prioritario', 'email_alerta_enviado']);
        });
    }
};

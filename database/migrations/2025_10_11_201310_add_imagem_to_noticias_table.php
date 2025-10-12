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
        Schema::table('noticias', function (Blueprint $table) {
            $table->string('url_imagem')->nullable()->after('url');
            $table->string('alt_imagem')->nullable()->after('url_imagem');
            $table->string('legenda_imagem')->nullable()->after('alt_imagem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('noticias', function (Blueprint $table) {
            $table->dropColumn(['url_imagem', 'alt_imagem', 'legenda_imagem']);
        });
    }
};

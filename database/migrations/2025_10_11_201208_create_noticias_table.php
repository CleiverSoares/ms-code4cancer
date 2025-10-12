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
        Schema::create('noticias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('resumo');
            $table->string('url');
            $table->string('fonte');
            $table->dateTime('data_publicacao');
            $table->string('categoria')->default('cancer');
            $table->boolean('ativa')->default(true);
            $table->timestamps();
            
            $table->index(['categoria', 'ativa']);
            $table->index('data_publicacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};

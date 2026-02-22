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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Informações pessoais
            $table->string('nome');
            $table->string('sobrenome')->nullable();
            $table->string('telefone')->unique();
            $table->string('email')->nullable();

            // Informações de vendas
            $table->string('produto');
            $table->string('origem')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('campanha')->nullable();
            $table->json('tags')->nullable();

            // Status e controle
            $table->string('status')->default('novo'); // novo, contatado, interessado, nao_interessado, convertido
            $table->integer('tentativas')->default(0);
            $table->timestamp('ultima_tentativa')->nullable();
            $table->string('resultado')->nullable();

            // Qualificação
            $table->string('interesse_nivel')->nullable(); // frio, morno, quente, muito_quente
            $table->string('proxima_acao')->nullable();
            $table->timestamp('data_proxima_acao')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('telefone');
            $table->index('email');
            $table->index('status');
            $table->index('campanha');
            $table->index('interesse_nivel');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

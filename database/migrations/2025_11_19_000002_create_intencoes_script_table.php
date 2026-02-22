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
        Schema::create('intencoes_script', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('script_id')->constrained('scripts')->onDelete('cascade');

            // Identificação
            $table->string('nome', 100); // 'confirmar_pagamento', 'negar_divida', etc
            $table->text('descricao')->nullable();

            // Detecção
            $table->json('palavras_chave'); // array de strings
            $table->json('regex_patterns')->nullable();

            // Ação a tomar
            $table->string('acao', 100); // 'gerar_proposta', 'agendar', 'transferir', etc
            $table->json('parametros_acao')->nullable();

            // Configurações
            $table->decimal('confianca_minima', 3, 2)->default(0.7);
            $table->integer('prioridade')->default(50); // 0-100

            // Fallback
            $table->foreignId('fallback_intencao_id')->nullable()->constrained('intencoes_script')->onDelete('set null');

            // Auditoria
            $table->string('criado_por', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('script_id');
            $table->index('nome');
            $table->index('acao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intencoes_script');
    }
};

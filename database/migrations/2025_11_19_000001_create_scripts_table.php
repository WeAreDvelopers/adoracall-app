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
        Schema::create('scripts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Informações básicas
            $table->string('nome');
            $table->enum('tipo', ['cobranca', 'vendas', 'retencao', 'follow_up', 'pesquisa', 'outro'])->default('outro');
            $table->text('descricao')->nullable();

            // Integração IA (Retell AI)
            $table->string('agente_id'); // Retell AI agent ID
            $table->string('agente_nome')->nullable();

            // Configurações de voz
            $table->string('voz', 50)->nullable(); // 'feminine', 'masculine', etc
            $table->string('idioma', 10)->default('pt-BR');
            $table->decimal('velocidade_fala', 3, 2)->default(1.0);

            // Horários de operação
            $table->integer('horario_inicio')->default(8); // 8h
            $table->integer('horario_fim')->default(20); // 20h
            $table->json('dias_semana')->nullable(); // [1,2,3,4,5] = seg-sex
            $table->boolean('excluir_feriados')->default(true);

            // Tentativas
            $table->integer('tentativas_max')->default(3);
            $table->integer('intervalo_entre_tentativas')->default(86400); // segundos (1 dia)

            // Status e controle
            $table->boolean('ativo')->default(true);
            $table->integer('versao')->default(1);
            $table->string('criado_por', 100)->nullable();

            // Configurações customizadas (JSON flexível)
            $table->json('configuracoes')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tipo');
            $table->index('ativo');
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scripts');
    }
};

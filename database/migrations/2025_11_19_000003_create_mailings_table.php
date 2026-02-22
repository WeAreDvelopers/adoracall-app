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
        Schema::create('mailings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('script_id')->constrained('scripts')->onDelete('cascade');

            // Informações básicas
            $table->string('nome');
            $table->text('descricao')->nullable();

            // Status do mailing
            $table->enum('status', [
                'rascunho',
                'pronto',
                'ativo',
                'pausado',
                'concluido',
                'cancelado'
            ])->default('rascunho');

            // Arquivo e contatos
            $table->string('arquivo_csv_path')->nullable();
            $table->integer('total_contatos')->default(0);

            // Estatísticas
            $table->integer('processados')->default(0);
            $table->integer('sucesso')->default(0);
            $table->integer('falhas')->default(0);
            $table->integer('abandonados')->default(0);
            $table->decimal('taxa_sucesso', 5, 2)->default(0);

            // Configurações de processamento
            $table->integer('velocidade_contatos_hora')->default(50); // throttle
            $table->enum('prioridade', ['alto', 'normal', 'baixo'])->default('normal');
            $table->integer('max_tentativas')->nullable(); // herda do script se NULL
            $table->integer('intervalo_retry')->nullable(); // herda do script se NULL

            // Filtros (JSON flexível)
            $table->json('filtros')->nullable(); // {"ddd": ["11","21"], "valor_min": 100, ...}

            // Período de execução
            $table->timestamp('data_inicio')->nullable();
            $table->timestamp('data_fim')->nullable();
            $table->timestamp('data_inicio_agendado')->nullable();
            $table->timestamp('data_fim_agendado')->nullable();

            // Pausas e retomas
            $table->timestamp('pausado_em')->nullable();
            $table->timestamp('retomado_em')->nullable();

            // Auditoria
            $table->string('criado_por', 100)->nullable();
            $table->string('atualizado_por', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('script_id');
            $table->index('status');
            $table->index('uuid');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailings');
    }
};

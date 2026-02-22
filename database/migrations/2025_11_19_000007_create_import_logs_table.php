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
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailing_id')->constrained()->onDelete('cascade');
            $table->string('arquivo_nome'); // Nome do arquivo CSV importado
            $table->integer('linha_numero'); // Número da linha no CSV
            $table->string('status'); // success, error, warning, skipped

            // Dados da linha processada
            $table->json('dados_originais')->nullable(); // Dados exatos da linha do CSV
            $table->json('dados_processados')->nullable(); // Dados após validação/formatação

            // Resultado do processamento
            $table->text('mensagem')->nullable(); // Mensagem de sucesso ou erro
            $table->json('erros_validacao')->nullable(); // Erros específicos de validação
            $table->string('acao_tomada')->nullable(); // inserted, updated, skipped, rejected

            // Contato criado (se houver)
            $table->foreignId('contato_id')->nullable()->constrained()->onDelete('set null');

            // Metadados do processamento
            $table->integer('tempo_processamento_ms')->nullable(); // Tempo de processamento em ms
            $table->string('ip_address')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable(); // ID do usuário (sem foreign key - tabela users não existe)

            $table->timestamps();

            // Índices para performance
            $table->index('mailing_id');
            $table->index('status');
            $table->index('linha_numero');
            $table->index('arquivo_nome');
            $table->index(['mailing_id', 'status']);
        });

        // Adicionar estatísticas de importação no mailing
        Schema::table('mailings', function (Blueprint $table) {
            $table->string('ultimo_arquivo_importado')->nullable()->after('taxa_sucesso');
            $table->timestamp('ultima_importacao_em')->nullable()->after('ultimo_arquivo_importado');
            $table->json('estatisticas_importacao')->nullable()->after('ultima_importacao_em');
            // Formato: {
            //   "total_linhas": 1000,
            //   "sucesso": 950,
            //   "erros": 30,
            //   "avisos": 20,
            //   "ignoradas": 0,
            //   "duplicadas": 50,
            //   "tempo_total_segundos": 45.5
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailings', function (Blueprint $table) {
            $table->dropColumn(['ultimo_arquivo_importado', 'ultima_importacao_em', 'estatisticas_importacao']);
        });

        Schema::dropIfExists('import_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_history', function (Blueprint $table) {
            $table->id();

            // Identificadores
            $table->string('call_id_retell')->unique()->index();
            $table->foreignId('ligacao_id')->nullable()->constrained('ligacoes')->onDelete('set null');
            $table->foreignId('contato_id')->nullable()->constrained('contatos')->onDelete('set null');

            // Informações básicas da chamada
            $table->string('agent_id')->nullable();
            $table->string('to_number')->nullable();
            $table->string('from_number')->nullable();
            $table->string('call_status')->index(); // ended, in_progress, error, registered
            $table->string('call_type')->nullable(); // phone_call, web_call
            $table->string('direction')->nullable(); // inbound, outbound

            // Timestamps da chamada
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('end_timestamp')->nullable();
            $table->integer('duration_ms')->default(0);

            // Análise e resultados
            $table->string('user_sentiment')->nullable()->index(); // Positive, Negative, Neutral, Unknown
            $table->boolean('call_successful')->default(false);
            $table->text('call_summary')->nullable(); // Resumo gerado pela IA
            $table->text('transcript')->nullable(); // Transcrição completa
            $table->text('transcript_preview')->nullable(); // Preview curto

            // Dados enriquecidos do contato (cache para performance)
            $table->string('cliente_nome')->nullable();
            $table->string('empresa_credora')->nullable();
            $table->decimal('valor_devido', 10, 2)->nullable();
            $table->date('vencimento')->nullable();
            $table->string('status_local')->nullable();
            $table->integer('tentativas')->default(0);

            // Metadados
            $table->string('recording_url')->nullable();
            $table->decimal('cost', 10, 4)->default(0);
            $table->string('cost_formatted')->nullable();
            $table->string('duration_formatted')->nullable();
            $table->json('metadata')->nullable(); // Outros dados em JSON
            $table->json('raw_data')->nullable(); // Dados brutos da Retell AI

            // Controle de sincronização
            $table->timestamp('synced_at')->nullable();
            $table->boolean('needs_resync')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Índices para performance (call_status e user_sentiment já têm índice inline acima)
            $table->index('start_timestamp');
            $table->index(['contato_id', 'start_timestamp']);
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_history');
    }
}

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
        Schema::create('ligacoes_vendas', function (Blueprint $table) {
            $table->id();

            // Relacionamento com lead
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');

            // Informações da chamada
            $table->string('sid_twilio')->nullable();
            $table->string('call_id_retell')->unique();
            $table->integer('duracao')->nullable(); // em segundos
            $table->string('status')->default('iniciada'); // iniciada, em_andamento, concluida, falhada
            $table->string('url_gravacao')->nullable();
            $table->string('resultado')->nullable(); // interessado, nao_interessado, callback, sem_resposta

            // Detalhes da conversa
            $table->json('detalhes')->nullable();
            $table->boolean('interesse_demonstrado')->default(false);
            $table->json('objecoes')->nullable();
            $table->text('proximos_passos')->nullable();
            $table->timestamp('agendamento_follow_up')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('call_id_retell');
            $table->index('status');
            $table->index('resultado');
            $table->index('lead_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ligacoes_vendas');
    }
};

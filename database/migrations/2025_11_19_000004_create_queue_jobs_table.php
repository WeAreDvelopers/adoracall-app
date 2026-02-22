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
        Schema::create('queue_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relacionamentos
            $table->foreignId('mailing_id')->constrained('mailings')->onDelete('cascade');
            $table->foreignId('contato_id')->constrained('contatos')->onDelete('cascade');

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending');

            // Tentativas
            $table->integer('tentativas')->default(0);
            $table->timestamp('proxima_tentativa')->nullable();
            $table->timestamp('ultima_tentativa')->nullable();

            // Resultado
            $table->json('resultado')->nullable(); // contém intenção detectada, transcrição, etc
            $table->integer('tempo_processamento')->nullable(); // em segundos
            $table->text('erro_mensagem')->nullable();

            // Worker que processa
            $table->string('worker_id', 100)->nullable();

            // Auditoria
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();

            // Índices
            $table->index('status');
            $table->index('mailing_id');
            $table->index('contato_id');
            $table->index('proxima_tentativa');
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_jobs');
    }
};

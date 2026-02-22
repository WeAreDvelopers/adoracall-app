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
        Schema::create('script_logs', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('script_id')->constrained('scripts')->onDelete('cascade');
            $table->foreignId('mailing_id')->nullable()->constrained('mailings')->onDelete('cascade');
            $table->foreignId('contato_id')->nullable()->constrained('contatos')->onDelete('cascade');

            // Evento
            $table->string('tipo_evento', 50); // 'script_criado', 'script_editado', 'mailing_iniciado', etc
            $table->json('detalhes')->nullable();

            // Auditoria
            $table->string('usuario_id', 100)->nullable();
            $table->string('endereco_ip', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index('script_id');
            $table->index('mailing_id');
            $table->index('tipo_evento');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('script_logs');
    }
};

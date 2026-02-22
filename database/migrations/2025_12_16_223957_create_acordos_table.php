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
        Schema::create('acordos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contato_id')->constrained('contatos')->onDelete('cascade');
            $table->foreignId('ligacao_id')->constrained('ligacoes')->onDelete('cascade');
            $table->foreignId('proposta_id')->constrained('propostas_pagamento')->onDelete('cascade');

            // Quando foi aceito
            $table->timestamp('aceito_em');

            // Confirmação do cliente
            $table->text('confirmacao_texto');
            $table->text('transcricao_trecho')->nullable();

            // Dados do acordo
            $table->decimal('valor_acordado', 15, 2);
            $table->integer('parcelas_acordadas');
            $table->decimal('valor_parcela_acordada', 15, 2)->nullable();

            // Link de pagamento
            $table->string('link_pagamento')->nullable();
            $table->string('codigo_boleto')->nullable();

            // Status
            $table->enum('status', ['pendente_pagamento', 'parcialmente_pago', 'pago', 'cancelado'])->default('pendente_pagamento');

            // Auditoria
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamps();
            $table->index('contato_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acordos');
    }
};

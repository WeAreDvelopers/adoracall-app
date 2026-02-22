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
        Schema::create('propostas_pagamento', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relacionamentos
            $table->foreignId('contato_id')->constrained('contatos')->onDelete('cascade');
            $table->foreignId('script_id')->nullable()->constrained('scripts')->onDelete('set null');
            $table->foreignId('mailing_id')->nullable()->constrained('mailings')->onDelete('set null');

            // Informações do devedor
            $table->string('telefone', 20);
            $table->string('nome_cliente')->nullable();
            $table->decimal('valor_original', 10, 2);
            $table->decimal('valor_proposta', 10, 2);
            $table->decimal('desconto', 5, 2)->default(0); // percentual

            // Informações da proposta
            $table->enum('tipo_proposta', ['pix', 'boleto', 'cartao', 'link_generico'])->default('link_generico');
            $table->string('link_pagamento', 500);
            $table->text('mensagem_sms')->nullable();

            // Status da proposta
            $table->enum('status', [
                'gerada',
                'sms_enviado',
                'sms_erro',
                'aguardando_pagamento',
                'pago',
                'expirado',
                'cancelado'
            ])->default('gerada');

            // Informações de envio SMS
            $table->string('twilio_message_sid')->nullable();
            $table->enum('sms_status', ['enviando', 'enviado', 'entregue', 'falhou', 'não_enviado'])->nullable();
            $table->text('sms_erro_mensagem')->nullable();
            $table->timestamp('sms_enviado_em')->nullable();
            $table->timestamp('sms_entregue_em')->nullable();

            // Informações de pagamento
            $table->string('gateway_id')->nullable(); // ID do gateway de pagamento
            $table->string('transaction_id')->nullable(); // ID da transação
            $table->timestamp('pago_em')->nullable();
            $table->decimal('valor_pago', 10, 2)->nullable();

            // Validade da proposta
            $table->timestamp('valido_ate')->nullable();
            $table->timestamp('expira_em')->nullable();

            // Metadados
            $table->json('metadata')->nullable(); // informações adicionais flexíveis

            // Auditoria
            $table->string('criado_por', 100)->nullable();
            $table->string('atualizado_por', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para performance
            $table->index('contato_id');
            $table->index('script_id');
            $table->index('mailing_id');
            $table->index('telefone');
            $table->index('status');
            $table->index('tipo_proposta');
            $table->index('sms_status');
            $table->index('uuid');
            $table->index('created_at');
            $table->index('pago_em');
            $table->index('twilio_message_sid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propostas_pagamento');
    }
};

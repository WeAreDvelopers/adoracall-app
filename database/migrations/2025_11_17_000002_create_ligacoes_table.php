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
        Schema::create('ligacoes', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->foreignId('contato_id')->constrained('contatos')->onDelete('cascade');

            // IDs externos
            $table->string('sid_twilio', 50)->nullable()->comment('SID da chamada no Twilio');
            $table->string('call_id_retell', 100)->nullable()->comment('Call ID do Retell AI');

            // Dados da chamada
            $table->integer('duracao')->nullable()->comment('Duração em segundos');
            $table->string('status', 30);
            $table->string('url_gravacao', 255)->nullable();
            $table->string('resultado', 50)->nullable();

            // Validação de segurança
            $table->integer('tentativas_validacao')->default(0)->comment('Número de tentativas de validação de CPF/Data Nascimento');
            $table->boolean('validacao_sucesso')->nullable()->comment('Se a validação de segurança foi bem-sucedida');
            $table->timestamp('validacao_timestamp')->nullable()->comment('Timestamp da última tentativa de validação');

            // Dados informados pelo cliente (auditoria)
            $table->string('cpf_informado', 3)->nullable()->comment('Primeiros 3 dígitos do CPF informados');
            $table->date('data_nascimento_informada')->nullable()->comment('Data de nascimento informada');

            // Detalhes adicionais (JSON)
            $table->json('detalhes')->nullable()->comment('Transcrição, intenções, sentimento, etc');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('call_id_retell');
            $table->index('sid_twilio');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ligacoes');
    }
};

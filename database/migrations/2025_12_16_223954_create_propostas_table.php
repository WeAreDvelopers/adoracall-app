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
        Schema::create('propostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contato_id')->constrained('contatos')->onDelete('cascade');

            // Tipo de proposta
            $table->enum('tipo', ['desconto_vista', 'parcelamento', 'entrada_parcelado'])->default('parcelamento');

            // Valores
            $table->decimal('valor_original', 15, 2);
            $table->decimal('valor_final', 15, 2);
            $table->decimal('desconto_percentual', 5, 2)->nullable();
            $table->integer('parcelas')->default(1);
            $table->decimal('valor_parcela', 15, 2)->nullable();
            $table->decimal('valor_entrada', 15, 2)->nullable();

            // Descrição para o agente
            $table->text('descricao_agente');

            // Status
            $table->enum('status', ['ativa', 'aceita', 'rejeitada', 'expirada'])->default('ativa');
            $table->timestamp('expira_em')->nullable();

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
        Schema::dropIfExists('propostas');
    }
};

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
        Schema::create('contatos', function (Blueprint $table) {
            $table->id();

            // Dados pessoais
            $table->string('nome', 100);
            $table->string('sobrenome', 100)->nullable();
            $table->string('telefone', 20);

            // Dados de segurança
            $table->text('cpf')->nullable()->comment('CPF criptografado do cliente');
            $table->string('cpf_primeiros_digitos', 3)->nullable()->comment('Primeiros 3 dígitos do CPF para validação');
            $table->date('data_nascimento')->nullable();

            // Dados financeiros
            $table->decimal('valor_debito', 10, 2);
            $table->date('vencimento');
            $table->string('empresa_credora', 100)->nullable()->comment('Empresa que originou a dívida');

            // Gestão da cobrança
            $table->string('campanha', 50)->nullable();
            $table->string('status', 30)->default('pendente');
            $table->integer('tentativas')->default(0);
            $table->timestamp('ultima_tentativa')->nullable();
            $table->string('resultado', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('cpf_primeiros_digitos');
            $table->index('telefone');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contatos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_publico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('slug', 80)->comment('Identificador único por empresa');
            $table->string('nome', 150);
            $table->string('subtitulo', 200)->nullable()->comment('Ex: 1 a 15 dias');
            $table->text('descricao')->nullable();
            $table->string('cor', 20)->default('#00194A')->comment('Cor do badge (hex)');
            $table->string('icone', 5)->default('A')->comment('Letra ou emoji do badge');

            // Configurações de discagem padrão
            $table->integer('max_tentativas')->default(6);
            $table->integer('dias_estimados')->default(2);
            $table->integer('velocidade_contatos_hora')->default(50);
            $table->integer('intervalo_retry')->default(240)->comment('Minutos entre tentativas');
            $table->string('prioridade', 20)->default('normal');
            $table->string('faixa_velocidade', 20)->nullable()->comment('Ex: 40-60');
            $table->text('insight')->nullable()->comment('Texto de dica para o operador');

            $table->integer('ordem')->default(0)->comment('Ordem de exibição');
            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->unique(['empresa_id', 'slug']);
            $table->index(['empresa_id', 'ativo', 'ordem']);
        });

        // Alterar coluna tipo_publico de enum para string no mailings
        // para aceitar slugs dinâmicos
        Schema::table('mailings', function (Blueprint $table) {
            $table->string('tipo_publico', 80)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_publico');
    }
};

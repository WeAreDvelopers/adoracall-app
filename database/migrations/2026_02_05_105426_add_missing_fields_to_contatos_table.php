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
        Schema::table('contatos', function (Blueprint $table) {
            // Histórico de inadimplência
            $table->string('historico_inadimplencia', 50)->default('primeira_vez')
                ->after('resultado')
                ->comment('primeira_vez, recorrente, etc');

            // Tentativas de contato específicas do job
            $table->integer('tentativas_contato')->default(1)
                ->after('historico_inadimplencia')
                ->comment('Quantidade de tentativas de contato');

            // Última ligação registrada
            $table->timestamp('ultima_ligacao')->nullable()
                ->after('tentativas_contato')
                ->comment('Timestamp da última ligação realizada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contatos', function (Blueprint $table) {
            $table->dropColumn([
                'historico_inadimplencia',
                'tentativas_contato',
                'ultima_ligacao'
            ]);
        });
    }
};

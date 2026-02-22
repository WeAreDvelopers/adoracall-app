<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandCallHistoryFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('call_history', function (Blueprint $table) {
            // Resultado da conversação
            if (!Schema::hasColumn('call_history', 'resultado_conversacao')) {
                $table->string('resultado_conversacao')->nullable()->default('desconhecido');
                // Valores possíveis: acordo, recusou, quer_pensar, escalado, erro, timeout
            }

            // Tipo de decisão
            if (!Schema::hasColumn('call_history', 'tipo_decisao')) {
                $table->string('tipo_decisao')->nullable();
                // Valores: conversado, auto_attendant, escalado
            }

            // Motivos por que não houve acordo
            if (!Schema::hasColumn('call_history', 'motivos_nao_conversao')) {
                $table->json('motivos_nao_conversao')->nullable();
            }

            // Notas deixadas pelo agente de voz
            if (!Schema::hasColumn('call_history', 'notas_agente')) {
                $table->text('notas_agente')->nullable();
            }

            // Taxa de conversão deste segmento
            if (!Schema::hasColumn('call_history', 'taxa_conversao_segmento')) {
                $table->decimal('taxa_conversao_segmento', 5, 2)->nullable();
            }

            // Criar índices para melhor performance
            $table->index('resultado_conversacao');
            $table->index('tipo_decisao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('call_history', function (Blueprint $table) {
            if (Schema::hasColumn('call_history', 'resultado_conversacao')) {
                $table->dropIndex(['resultado_conversacao']);
            }
            if (Schema::hasColumn('call_history', 'tipo_decisao')) {
                $table->dropIndex(['tipo_decisao']);
            }

            $table->dropColumn([
                'resultado_conversacao',
                'tipo_decisao',
                'motivos_nao_conversao',
                'notas_agente',
                'taxa_conversao_segmento'
            ]);
        });
    }
}

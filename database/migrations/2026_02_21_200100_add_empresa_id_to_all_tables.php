<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddEmpresaIdToAllTables extends Migration
{
    /**
     * Tabelas que recebem empresa_id
     */
    private array $tables = [
        'users',
        'mailings',
        'contatos',
        'queue_jobs',
        'ligacoes',
        'scripts',
        'propostas_pagamento',
        'acordos',
        'import_logs',
        'call_history',
        'leads',
        'ligacoes_vendas',
        'script_logs',
        'intencoes_script',
        'propostas',
    ];

    public function up()
    {
        // Buscar empresa padrão (criada na migration anterior)
        $empresaPadrao = DB::table('empresas')->where('slug', 'empresa-padrao')->first();

        if (!$empresaPadrao) {
            throw new \RuntimeException('Empresa padrão não encontrada. Execute a migration create_empresas_table primeiro.');
        }

        $empresaId = $empresaPadrao->id;

        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            // Limpar estado parcial: se coluna existe mas sem FK, dropar e refazer
            if (Schema::hasColumn($tableName, 'empresa_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Tentar dropar FK e index se existem (ignorar erro se não existem)
                    try { $table->dropForeign("{$tableName}_empresa_id_foreign"); } catch (\Exception $e) {}
                    try { $table->dropIndex("{$tableName}_empresa_id_index"); } catch (\Exception $e) {}
                    $table->dropColumn('empresa_id');
                });
            }

            // 1. Adicionar coluna como NULLABLE
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->nullable();
            });

            // 2. Atualizar registros existentes com empresa padrão
            DB::table($tableName)->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);

            // 3. Tornar NOT NULL via raw SQL (MySQL syntax, sem doctrine/dbal)
            DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `empresa_id` BIGINT UNSIGNED NOT NULL");

            // 4. Adicionar index e FK
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->index('empresa_id', "{$tableName}_empresa_id_index");

                $table->foreign('empresa_id', "{$tableName}_empresa_id_foreign")
                    ->references('id')
                    ->on('empresas')
                    ->onDelete('restrict');
            });
        }
    }

    public function down()
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'empresa_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign("{$tableName}_empresa_id_foreign");
                $table->dropIndex("{$tableName}_empresa_id_index");
                $table->dropColumn('empresa_id');
            });
        }
    }
}

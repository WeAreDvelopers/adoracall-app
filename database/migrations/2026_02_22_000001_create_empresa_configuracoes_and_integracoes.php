<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CreateEmpresaConfiguracoesAndIntegracoes extends Migration
{
    public function up()
    {
        // 1. Tabela de configurações da fila/cobrança
        Schema::create('empresa_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->unique();
            $table->string('nome_credora', 255)->nullable();
            $table->decimal('percentual_desconto_alto', 5, 2)->default(10.00);
            $table->decimal('percentual_desconto_baixo', 5, 2)->default(5.00);
            $table->decimal('limite_valor_desconto_alto', 12, 2)->default(500.00);
            $table->integer('max_parcelas')->default(3);
            $table->decimal('valor_minimo_parcela', 12, 2)->default(100.00);
            $table->timestamps();

            $table->foreign('empresa_id')
                  ->references('id')->on('empresas')
                  ->onDelete('cascade');
        });

        // 2. Tabela de integrações (Retell AI)
        Schema::create('empresa_integracoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->unique();
            $table->text('retell_api_key')->nullable();
            $table->string('retell_agent_id', 255)->nullable();
            $table->string('retell_agent_id_sales', 255)->nullable();
            $table->text('retell_webhook_secret')->nullable();
            $table->string('retell_from_number', 20)->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')
                  ->references('id')->on('empresas')
                  ->onDelete('cascade');
        });

        // 3. Migrar dados existentes do JSON configuracoes
        $this->migrateExistingData();
    }

    private function migrateExistingData()
    {
        $now = Carbon::now();
        $empresas = DB::table('empresas')->get();

        foreach ($empresas as $empresa) {
            $config = $empresa->configuracoes
                ? json_decode($empresa->configuracoes, true)
                : [];

            // Inserir configurações da fila
            DB::table('empresa_configuracoes')->insert([
                'empresa_id'                 => $empresa->id,
                'nome_credora'               => $config['nome_credora'] ?? null,
                'percentual_desconto_alto'   => $config['percentual_desconto_alto'] ?? 10,
                'percentual_desconto_baixo'  => $config['percentual_desconto_baixo'] ?? 5,
                'limite_valor_desconto_alto'  => $config['limite_valor_desconto_alto'] ?? 500,
                'max_parcelas'               => $config['max_parcelas'] ?? 3,
                'valor_minimo_parcela'       => $config['valor_minimo_parcela'] ?? 100,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);

            // Inserir integrações (somente se existir alguma credencial)
            $hasIntegracoes = !empty($config['integracao_retell_api_key'])
                || !empty($config['integracao_retell_agent_id'])
                || !empty($config['integracao_retell_agent_id_sales'])
                || !empty($config['integracao_retell_webhook_secret'])
                || !empty($config['integracao_retell_from_number']);

            if ($hasIntegracoes) {
                DB::table('empresa_integracoes')->insert([
                    'empresa_id'            => $empresa->id,
                    'retell_api_key'        => $config['integracao_retell_api_key'] ?? null,
                    'retell_agent_id'       => $config['integracao_retell_agent_id'] ?? null,
                    'retell_agent_id_sales' => $config['integracao_retell_agent_id_sales'] ?? null,
                    'retell_webhook_secret' => $config['integracao_retell_webhook_secret'] ?? null,
                    'retell_from_number'    => $config['integracao_retell_from_number'] ?? null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('empresa_integracoes');
        Schema::dropIfExists('empresa_configuracoes');
    }
}

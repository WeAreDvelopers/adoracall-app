<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAtendenteFieldsToEmpresaConfiguracoes extends Migration
{
    public function up()
    {
        Schema::table('empresa_configuracoes', function (Blueprint $table) {
            $table->string('nome_atendente', 100)->nullable()->after('nome_credora');
            $table->string('artigo_empresa', 2)->nullable()->after('nome_atendente');
            $table->string('modo_ligacao', 10)->default('ivr')->after('artigo_empresa');
        });
    }

    public function down()
    {
        Schema::table('empresa_configuracoes', function (Blueprint $table) {
            $table->dropColumn(['nome_atendente', 'artigo_empresa', 'modo_ligacao']);
        });
    }
}

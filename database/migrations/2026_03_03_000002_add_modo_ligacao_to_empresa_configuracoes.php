<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModoLigacaoToEmpresaConfiguracoes extends Migration
{
    public function up()
    {
        Schema::table('empresa_configuracoes', function (Blueprint $table) {
            $table->string('modo_ligacao', 10)->default('ivr')->after('artigo_empresa');
        });
    }

    public function down()
    {
        Schema::table('empresa_configuracoes', function (Blueprint $table) {
            $table->dropColumn('modo_ligacao');
        });
    }
}

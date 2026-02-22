<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateEmpresasTable extends Migration
{
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('cnpj', 18)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->text('endereco')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('ativa')->default(true);
            $table->json('configuracoes')->nullable();
            $table->integer('max_usuarios')->default(10);
            $table->timestamps();
            $table->softDeletes();
        });

        // Criar empresa padrão
        $empresaId = DB::table('empresas')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'nome' => 'Empresa Padrão',
            'slug' => 'empresa-padrao',
            'ativa' => true,
            'max_usuarios' => 100,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Guardar o ID para a próxima migration
        // Usar cache temporário via config
        config(['empresa_padrao_id' => $empresaId]);
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
}

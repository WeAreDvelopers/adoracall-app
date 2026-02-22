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
        Schema::table('mailings', function (Blueprint $table) {
            $table->enum('tipo_publico', [
                'atraso_leve',
                'atraso_medio',
                'atraso_alto',
                'inadimplencia_critica',
                'leads_novos'
            ])->nullable()->after('descricao')->comment('Tipo de público/estratégia (Atraso Leve, Atraso Médio, etc)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailings', function (Blueprint $table) {
            $table->dropColumn('tipo_publico');
        });
    }
};

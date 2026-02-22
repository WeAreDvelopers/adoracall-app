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
        Schema::table('propostas_pagamento', function (Blueprint $table) {
            // Adicionar campos numéricos para armazenar valores corretamente
            $table->decimal('valor_original_numerico', 10, 2)->nullable()->after('valor_original')->comment('Valor original em formato numérico');
            $table->decimal('valor_proposta_numerico', 10, 2)->nullable()->after('valor_proposta')->comment('Valor proposta em formato numérico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propostas_pagamento', function (Blueprint $table) {
            $table->dropColumn(['valor_original_numerico', 'valor_proposta_numerico']);
        });
    }
};

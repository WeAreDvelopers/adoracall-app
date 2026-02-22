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
            // Adiciona relacionamento com mailings (após a coluna campanha)
            $table->foreignId('mailing_id')->nullable()->after('campanha')->constrained('mailings')->onDelete('set null');

            // Índice
            $table->index('mailing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contatos', function (Blueprint $table) {
            $table->dropForeign(['mailing_id']);
            $table->dropColumn('mailing_id');
        });
    }
};

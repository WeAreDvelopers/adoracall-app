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
        Schema::table('ligacoes', function (Blueprint $table) {
            // Adicionar coluna de relacionamento com acordo
            $table->unsignedBigInteger('acordo_id')->nullable()->after('contato_id');
            $table->foreign('acordo_id')->references('id')->on('acordos')->onDelete('set null');

            // Adicionar coluna para rastrear se ligação foi atendida
            $table->boolean('foi_atendida')->default(false)->after('acordo_id');

            // Adicionar flag de sincronização com Retell
            $table->boolean('sincronizado_retell')->default(false)->after('call_id_retell');

            // Adicionar timestamp de última sincronização
            $table->timestamp('ultimo_sync_retell')->nullable()->after('sincronizado_retell');

            // Índices para buscas rápidas
            $table->index('acordo_id');
            $table->index('foi_atendida');
            $table->index('sincronizado_retell');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ligacoes', function (Blueprint $table) {
            $table->dropForeign(['acordo_id']);
            $table->dropIndex(['acordo_id']);
            $table->dropIndex(['foi_atendida']);
            $table->dropIndex(['sincronizado_retell']);

            $table->dropColumn([
                'acordo_id',
                'foi_atendida',
                'sincronizado_retell',
                'ultimo_sync_retell',
            ]);
        });
    }
};

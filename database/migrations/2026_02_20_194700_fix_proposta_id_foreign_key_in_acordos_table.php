<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('acordos', function (Blueprint $table) {
            // Drop existing foreign key constraint
            if (DB::getDriverName() === 'mysql') {
                $table->dropForeign(['proposta_id']);
                // Add new foreign key pointing to propostas_pagamento
                $table->foreign('proposta_id')
                    ->references('id')
                    ->on('propostas_pagamento')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acordos', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                $table->dropForeign(['proposta_id']);
                // Restore old foreign key pointing to propostas
                $table->foreign('proposta_id')
                    ->references('id')
                    ->on('propostas')
                    ->onDelete('cascade');
            }
        });
    }
};

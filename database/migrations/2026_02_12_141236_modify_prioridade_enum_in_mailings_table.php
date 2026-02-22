<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE mailings MODIFY COLUMN prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE mailings MODIFY COLUMN prioridade ENUM('alto', 'normal', 'baixo') DEFAULT 'normal'");
    }
};

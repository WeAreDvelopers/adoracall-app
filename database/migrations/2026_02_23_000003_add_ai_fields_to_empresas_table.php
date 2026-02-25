<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('use_ai')->default(false)->after('ativa');
            $table->string('ai_model')->nullable()->after('use_ai');
        });

        Schema::table('empresa_integracoes', function (Blueprint $table) {
            $table->text('openai_api_key')->nullable()->after('twilio_from_number');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['use_ai', 'ai_model']);
        });

        Schema::table('empresa_integracoes', function (Blueprint $table) {
            $table->dropColumn('openai_api_key');
        });
    }
};

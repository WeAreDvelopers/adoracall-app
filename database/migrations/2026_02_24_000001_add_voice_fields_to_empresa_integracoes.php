<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVoiceFieldsToEmpresaIntegracoes extends Migration
{
    public function up()
    {
        Schema::table('empresa_integracoes', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_integracoes', 'twilio_voice')) {
                $table->string('twilio_voice')->nullable()->after('twilio_from_number');
            }
            if (!Schema::hasColumn('empresa_integracoes', 'twilio_speech_rate')) {
                $table->string('twilio_speech_rate')->nullable()->after('twilio_voice');
            }
        });
    }

    public function down()
    {
        Schema::table('empresa_integracoes', function (Blueprint $table) {
            $table->dropColumn(['twilio_voice', 'twilio_speech_rate']);
        });
    }
}

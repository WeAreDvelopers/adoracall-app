<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTwilioFieldsToEmpresaIntegracoes extends Migration
{
    public function up()
    {
        Schema::table('empresa_integracoes', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_integracoes', 'twilio_account_sid')) {
                $table->text('twilio_account_sid')->nullable()->after('retell_from_number');
            }
            if (!Schema::hasColumn('empresa_integracoes', 'twilio_auth_token')) {
                $table->text('twilio_auth_token')->nullable()->after('twilio_account_sid');
            }
            if (!Schema::hasColumn('empresa_integracoes', 'twilio_from_number')) {
                $table->string('twilio_from_number')->nullable()->after('twilio_auth_token');
            }
        });
    }

    public function down()
    {
        Schema::table('empresa_integracoes', function (Blueprint $table) {
            $table->dropColumn(['twilio_account_sid', 'twilio_auth_token', 'twilio_from_number']);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUraCallsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ura_calls')) {
            return;
        }

        Schema::create('ura_calls', function (Blueprint $table) {
            $table->id();
            $table->string('call_sid')->unique()->nullable();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('contato_id');
            $table->unsignedBigInteger('mailing_id')->nullable();
            $table->unsignedBigInteger('queue_job_id')->nullable();
            $table->string('cpf')->nullable();
            $table->string('step')->default('START');
            $table->json('selected_option')->nullable();
            $table->string('result')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('duracao')->nullable();
            $table->string('status_twilio')->nullable();
            $table->timestamps();

            $table->index('empresa_id');
            $table->index('contato_id');
            $table->index('mailing_id');
            $table->index('queue_job_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ura_calls');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToEmpresa;

class UraCall extends Model
{
    use BelongsToEmpresa;

    protected $table = 'ura_calls';

    const STEP_START                  = 'START';
    const STEP_IDENTIDADE_CONFIRMADA  = 'IDENTIDADE_CONFIRMADA';
    const STEP_DIVIDA_INFORMADA       = 'DIVIDA_INFORMADA';
    const STEP_CPF_RECEBIDO           = 'CPF_RECEBIDO';
    const STEP_PROPOSTA_APRESENTADA   = 'PROPOSTA_APRESENTADA';
    const STEP_CONFIRMANDO            = 'CONFIRMANDO';
    const STEP_FINALIZADO             = 'FINALIZADO';

    protected $fillable = [
        'call_sid',
        'empresa_id',
        'contato_id',
        'mailing_id',
        'queue_job_id',
        'cpf',
        'step',
        'selected_option',
        'result',
        'error_message',
        'duracao',
        'status_twilio',
    ];

    protected $casts = [
        'selected_option' => 'array',
        'duracao'         => 'integer',
        'contato_id'      => 'integer',
        'mailing_id'      => 'integer',
        'queue_job_id'    => 'integer',
    ];

    /**
     * Busca UraCall pelo CallSid do Twilio.
     * Usa withoutGlobalScopes() pois webhooks não têm auth/empresa_id.
     */
    public static function findByCallSid(string $callSid): ?self
    {
        return static::withoutGlobalScopes()
            ->where('call_sid', $callSid)
            ->first();
    }

    public function contato()
    {
        return $this->belongsTo(Contato::class);
    }

    public function mailing()
    {
        return $this->belongsTo(Mailing::class);
    }

    public function queueJob()
    {
        return $this->belongsTo(QueueJob::class, 'queue_job_id');
    }
}

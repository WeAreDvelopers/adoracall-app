<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BelongsToEmpresa;

class PropostaPagamento extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'propostas_pagamento';

    protected $fillable = [
        'uuid',
        'contato_id',
        'script_id',
        'mailing_id',
        'telefone',
        'nome_cliente',
        'valor_original',
        'valor_original_numerico',
        'valor_proposta',
        'valor_proposta_numerico',
        'desconto',
        'tipo_proposta',
        'link_pagamento',
        'mensagem_sms',
        'status',
        'twilio_message_sid',
        'sms_status',
        'sms_erro_mensagem',
        'sms_enviado_em',
        'sms_entregue_em',
        'gateway_id',
        'transaction_id',
        'pago_em',
        'valor_pago',
        'valido_ate',
        'expira_em',
        'metadata',
        'criado_por',
        'atualizado_por',
        'empresa_id',
    ];

    protected $casts = [
        'contato_id'      => 'integer',
        'script_id'       => 'integer',
        'mailing_id'      => 'integer',
        'sms_enviado_em'  => 'datetime',
        'sms_entregue_em' => 'datetime',
        'pago_em'         => 'datetime',
        'valido_ate'      => 'datetime',
        'expira_em'       => 'datetime',
        'metadata'        => 'array',
    ];

    // Eventos do modelo
    protected static function boot()
    {
        parent::boot();

        // Gera UUID automaticamente ao criar
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            // Define validade padrão (7 dias)
            if (empty($model->valido_ate)) {
                $model->valido_ate = Carbon::now()->addDays(7);
            }

            // Define expiração padrão (mesma da validade)
            if (empty($model->expira_em)) {
                $model->expira_em = $model->valido_ate;
            }
        });
    }

    // Relacionamentos

    /**
     * Contato associado à proposta
     */
    public function contato()
    {
        return $this->belongsTo(Contato::class, 'contato_id');
    }

    /**
     * Script associado à proposta
     */
    public function script()
    {
        return $this->belongsTo(Script::class, 'script_id');
    }

    /**
     * Mailing associado à proposta
     */
    public function mailing()
    {
        return $this->belongsTo(Mailing::class, 'mailing_id');
    }

    // Métodos auxiliares

    /**
     * Marca a proposta como SMS enviado
     */
    public function marcarSmsEnviado(string $messageSid): bool
    {
        $this->twilio_message_sid = $messageSid;
        $this->sms_status         = 'enviado';
        $this->sms_enviado_em     = Carbon::now();
        $this->status             = 'sms_enviado';
        return $this->save();
    }

    /**
     * Marca a proposta como SMS entregue
     */
    public function marcarSmsEntregue(): bool
    {
        $this->sms_status      = 'entregue';
        $this->sms_entregue_em = Carbon::now();
        $this->status          = 'aguardando_pagamento';
        return $this->save();
    }

    /**
     * Marca a proposta como SMS com erro
     */
    public function marcarSmsErro(string $mensagemErro): bool
    {
        $this->sms_status        = 'falhou';
        $this->sms_erro_mensagem = $mensagemErro;
        $this->status            = 'sms_erro';
        return $this->save();
    }

    /**
     * Marca a proposta como paga
     */
    public function marcarPago(float $valorPago, ?string $transactionId = null): bool
    {
        $this->status     = 'pago';
        $this->pago_em    = Carbon::now();
        $this->valor_pago = $valorPago;

        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }

        return $this->save();
    }

    /**
     * Verifica se a proposta está expirada
     */
    public function estaExpirada(): bool
    {
        return $this->expira_em && Carbon::now()->gt($this->expira_em);
    }

    /**
     * Marca como expirada se necessário
     */
    public function verificarExpiracao(): bool
    {
        if ($this->estaExpirada() && $this->status === 'aguardando_pagamento') {
            $this->status = 'expirado';
            return $this->save();
        }

        return false;
    }

    /**
     * Calcula o desconto aplicado
     */
    public function calcularDesconto(): float
    {
        if ($this->valor_original > 0) {
            return (($this->valor_original - $this->valor_proposta) / $this->valor_original) * 100;
        }

        return 0;
    }

    // Scopes

    /**
     * Propostas por status
     */
    public function scopePorStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Propostas de um contato
     */
    public function scopePorContato($query, int $contatoId)
    {
        return $query->where('contato_id', $contatoId);
    }

    /**
     * Propostas de um mailing
     */
    public function scopePorMailing($query, int $mailingId)
    {
        return $query->where('mailing_id', $mailingId);
    }

    /**
     * Propostas pendentes (aguardando pagamento)
     */
    public function scopePendentes($query)
    {
        return $query->where('status', 'aguardando_pagamento');
    }

    /**
     * Propostas pagas
     */
    public function scopePagas($query)
    {
        return $query->where('status', 'pago');
    }

    /**
     * Propostas expiradas
     */
    public function scopeExpiradas($query)
    {
        return $query->where('status', 'expirado')
            ->orWhere(function ($q) {
                $q->where('expira_em', '<', Carbon::now())
                    ->whereIn('status', ['gerada', 'sms_enviado', 'aguardando_pagamento']);
            });
    }

    /**
     * Propostas por período
     */
    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('created_at', [$dataInicio, $dataFim]);
    }

    /**
     * Propostas por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_proposta', $tipo);
    }
}

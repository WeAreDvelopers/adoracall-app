<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Traits\BelongsToEmpresa;

class QueueJob extends Model
{
    use BelongsToEmpresa;

    protected $table = 'queue_jobs';

    protected $fillable = [
        'uuid',
        'mailing_id',
        'contato_id',
        'status',
        'tentativas',
        'proxima_tentativa',
        'ultima_tentativa',
        'resultado',
        'tempo_processamento',
        'erro_mensagem',
        'worker_id',
        'completed_at',
        'empresa_id',
    ];

    protected $casts = [
        'mailing_id' => 'integer',
        'contato_id' => 'integer',
        'tentativas' => 'integer',
        'tempo_processamento' => 'integer',
        'resultado' => 'array',
        'proxima_tentativa' => 'datetime',
        'ultima_tentativa' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relacionamentos

    /**
     * Mailing ao qual este job pertence
     */
    public function mailing()
    {
        return $this->belongsTo(Mailing::class, 'mailing_id');
    }

    /**
     * Contato a ser processado
     */
    public function contato()
    {
        return $this->belongsTo(Contato::class, 'contato_id');
    }

    // Métodos auxiliares

    /**
     * Verifica se está pendente
     */
    public function isPendente(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica se está processando
     */
    public function isProcessando(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Verifica se foi completado
     */
    public function isCompletado(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Verifica se falhou
     */
    public function isFalhou(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Marca como processando
     */
    public function marcarComoProcessando(string $workerId = null): bool
    {
        $this->status = 'processing';
        $this->worker_id = $workerId ?? gethostname();
        $this->ultima_tentativa = Carbon::now();
        return $this->save();
    }

    /**
     * Marca como completado
     */
    public function marcarComoCompletado(array $resultado = null, int $tempoProcessamento = null): bool
    {
        $this->status = 'completed';
        $this->resultado = $resultado;
        $this->tempo_processamento = $tempoProcessamento;
        $this->completed_at = Carbon::now();
        return $this->save();
    }

    /**
     * Marca como falhou
     */
    public function marcarComoFalhou(string $mensagemErro = null): bool
    {
        $this->tentativas += 1;
        $this->erro_mensagem = $mensagemErro;

        $mailing = $this->mailing;
        $maxTentativas = $mailing->max_tentativas ?? $mailing->script->tentativas_max ?? 3;

        if ($this->tentativas >= $maxTentativas) {
            $this->status = 'failed';
        } else {
            // Agenda retry com backoff exponencial
            $delay = pow(2, $this->tentativas) * 60; // 2min, 4min, 8min...
            $this->proxima_tentativa = Carbon::now()->addSeconds($delay);
            $this->status = 'pending';
        }

        return $this->save();
    }

    /**
     * Reseta para retry manual
     */
    public function resetarParaRetry(): bool
    {
        $this->status = 'pending';
        $this->tentativas = 0;
        $this->proxima_tentativa = Carbon::now();
        $this->erro_mensagem = null;
        return $this->save();
    }

    // Scopes

    /**
     * Jobs pendentes prontos para processar
     */
    public function scopePendentes($query)
    {
        return $query->where('status', 'pending')
            ->where(function($q) {
                $q->whereNull('proxima_tentativa')
                  ->orWhere('proxima_tentativa', '<=', Carbon::now());
            });
    }

    /**
     * Jobs por status
     */
    public function scopePorStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Jobs de um mailing específico
     */
    public function scopePorMailing($query, int $mailingId)
    {
        return $query->where('mailing_id', $mailingId);
    }

    /**
     * Jobs que falharam
     */
    public function scopeFalhos($query)
    {
        return $query->where('status', 'failed');
    }
}

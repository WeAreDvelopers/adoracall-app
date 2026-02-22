<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Traits\BelongsToEmpresa;

class Mailing extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'mailings';

    protected $fillable = [
        'uuid',
        'script_id',
        'nome',
        'descricao',
        'tipo_publico',
        'status',
        'arquivo_csv_path',
        'total_contatos',
        'processados',
        'sucesso',
        'falhas',
        'abandonados',
        'taxa_sucesso',
        'velocidade_contatos_hora',
        'prioridade',
        'max_tentativas',
        'intervalo_retry',
        'filtros',
        'data_inicio',
        'data_fim',
        'data_inicio_agendado',
        'data_fim_agendado',
        'pausado_em',
        'retomado_em',
        'criado_por',
        'atualizado_por',
        'ultimo_arquivo_importado',
        'ultima_importacao_em',
        'estatisticas_importacao',
        'empresa_id',
    ];

    protected $casts = [
        'filtros'                  => 'array',
        'total_contatos'           => 'integer',
        'processados'              => 'integer',
        'sucesso'                  => 'integer',
        'falhas'                   => 'integer',
        'abandonados'              => 'integer',
        'taxa_sucesso'             => 'decimal:2',
        'velocidade_contatos_hora' => 'integer',
        'max_tentativas'           => 'integer',
        'intervalo_retry'          => 'integer',
        'script_id'                => 'integer',
        'data_inicio'              => 'datetime',
        'data_fim'                 => 'datetime',
        'data_inicio_agendado'     => 'datetime',
        'data_fim_agendado'        => 'datetime',
        'pausado_em'               => 'datetime',
        'retomado_em'              => 'datetime',
        'ultima_importacao_em'     => 'datetime',
        'estatisticas_importacao'  => 'array',
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
     * Script utilizado neste mailing
     */
    public function script()
    {
        return $this->belongsTo(Script::class, 'script_id');
    }

    /**
     * Contatos deste mailing
     */
    public function contatos()
    {
        return $this->hasMany(Contato::class, 'mailing_id');
    }

    /**
     * Jobs da fila
     */
    public function queueJobs()
    {
        return $this->hasMany(QueueJob::class, 'mailing_id');
    }

    /**
     * Logs de importação
     */
    public function importLogs()
    {
        return $this->hasMany(ImportLog::class, 'mailing_id');
    }

    /**
     * Logs do mailing
     */
    public function logs()
    {
        return $this->hasMany(ScriptLog::class, 'mailing_id');
    }

    // Métodos auxiliares

    /**
     * Verifica se o mailing está ativo
     */
    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Verifica se o mailing está pausado
     */
    public function isPausado(): bool
    {
        return $this->status === 'pausado';
    }

    /**
     * Pausa o mailing
     */
    public function pausar(): bool
    {
        if ($this->status !== 'ativo') {
            return false;
        }

        $this->status     = 'pausado';
        $this->pausado_em = Carbon::now();
        return $this->save();
    }

    /**
     * Retoma o mailing
     */
    public function retomar(): bool
    {
        if ($this->status !== 'pausado') {
            return false;
        }

        $this->status      = 'ativo';
        $this->retomado_em = Carbon::now();
        return $this->save();
    }

    /**
     * Cancela o mailing
     */
    public function cancelar(): bool
    {
        if (in_array($this->status, ['concluido', 'cancelado'])) {
            return false;
        }

        $this->status = 'cancelado';
        return $this->save();
    }

    /**
     * Ativa o mailing
     */
    public function ativar(): bool
    {
        if (!in_array($this->status, ['pronto', 'rascunho'])) {
            return false;
        }

        $this->status      = 'ativo';
        $this->data_inicio = Carbon::now();
        return $this->save();
    }

    /**
     * Calcula e atualiza estatísticas
     */
    public function atualizarEstatisticas(): void
    {
        $stats = $this->queueJobs()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as sucesso,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as falhas,
                SUM(CASE WHEN status IN ('completed', 'failed') THEN 1 ELSE 0 END) as processados
            ")
            ->first();

        $this->processados = $stats->processados ?? 0;
        $this->sucesso     = $stats->sucesso ?? 0;
        $this->falhas      = $stats->falhas ?? 0;

        if ($this->processados > 0) {
            $this->taxa_sucesso = round(($this->sucesso / $this->processados) * 100, 2);
        }

        // Verifica se completou
        if ($this->processados >= $this->total_contatos && $this->total_contatos > 0) {
            $this->status   = 'concluido';
            $this->data_fim = Carbon::now();
        }

        $this->save();
    }

    /**
     * Obtém progresso em porcentagem
     */
    public function getProgressoAttribute(): float
    {
        if ($this->total_contatos == 0) {
            return 0;
        }

        return round(($this->processados / $this->total_contatos) * 100, 2);
    }

    // Scopes

    /**
     * Apenas mailings ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Mailings por status
     */
    public function scopePorStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Mailings por prioridade
     */
    public function scopePorPrioridade($query, string $prioridade)
    {
        return $query->where('prioridade', $prioridade);
    }

    /**
     * Mailings recentes primeiro
     */
    public function scopeRecentes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}

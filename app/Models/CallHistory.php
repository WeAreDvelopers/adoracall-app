<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Traits\BelongsToEmpresa;

class CallHistory extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'call_history';

    protected $fillable = [
        'call_id_retell',
        'ligacao_id',
        'contato_id',
        'agent_id',
        'to_number',
        'from_number',
        'call_status',
        'call_type',
        'direction',
        'start_timestamp',
        'end_timestamp',
        'duration_ms',
        'user_sentiment',
        'call_successful',
        'call_summary',
        'transcript',
        'transcript_preview',
        'cliente_nome',
        'empresa_credora',
        'valor_devido',
        'vencimento',
        'status_local',
        'tentativas',
        'recording_url',
        'cost',
        'cost_formatted',
        'duration_formatted',
        'metadata',
        'raw_data',
        'synced_at',
        'needs_resync',
        'empresa_id',
    ];

    protected $casts = [
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
        'duration_ms' => 'integer',
        'call_successful' => 'boolean',
        'valor_devido' => 'decimal:2',
        'vencimento' => 'date',
        'tentativas' => 'integer',
        'cost' => 'decimal:4',
        'metadata' => 'array',
        'raw_data' => 'array',
        'synced_at' => 'datetime',
        'needs_resync' => 'boolean',
    ];

    /**
     * Relacionamento com Ligação
     */
    public function ligacao()
    {
        return $this->belongsTo(Ligacao::class, 'ligacao_id');
    }

    /**
     * Relacionamento com Contato
     */
    public function contato()
    {
        return $this->belongsTo(Contato::class, 'contato_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Filtra por status
     */
    public function scopeComStatus($query, string $status)
    {
        return $query->where('call_status', $status);
    }

    /**
     * Filtra por sentimento
     */
    public function scopeComSentimento($query, string $sentiment)
    {
        return $query->where('user_sentiment', $sentiment);
    }

    /**
     * Filtra por tipo
     */
    public function scopeComTipo($query, string $type)
    {
        return $query->where('call_type', $type);
    }

    /**
     * Filtra por período
     */
    public function scopePorPeriodo($query, Carbon $inicio, Carbon $fim)
    {
        return $query->whereBetween('start_timestamp', [$inicio, $fim]);
    }

    /**
     * Últimos N dias
     */
    public function scopeUltimosDias($query, int $dias = 30)
    {
        return $query->where('start_timestamp', '>=', Carbon::now()->subDays($dias));
    }

    /**
     * Chamadas bem-sucedidas
     */
    public function scopeBemSucedidas($query)
    {
        return $query->where('call_successful', true);
    }

    /**
     * Chamadas que precisam resync
     */
    public function scopePrecisaResync($query)
    {
        return $query->where('needs_resync', true);
    }

    /**
     * Ordenar por mais recentes
     */
    public function scopeMaisRecentes($query)
    {
        return $query->orderBy('start_timestamp', 'desc');
    }

    // ============================================
    // MÉTODOS HELPER
    // ============================================

    /**
     * Marca como sincronizado
     */
    public function marcarComoSincronizado(): bool
    {
        $this->synced_at = Carbon::now();
        $this->needs_resync = false;
        return $this->save();
    }

    /**
     * Marca para resincronização
     */
    public function marcarParaResync(): bool
    {
        $this->needs_resync = true;
        return $this->save();
    }

    /**
     * Verifica se tem dados locais enriquecidos
     */
    public function temDadosLocais(): bool
    {
        return !empty($this->cliente_nome) && !empty($this->contato_id);
    }

    /**
     * Retorna dados formatados para o frontend
     */
    public function paraFrontend(): array
    {
        return [
            'call_id' => $this->call_id_retell,
            'to_number' => $this->to_number,
            'from_number' => $this->from_number,
            'call_status' => $this->call_status,
            'call_type' => $this->call_type,
            'direction' => $this->direction,
            'start_timestamp' => $this->start_timestamp?->toIso8601String(),
            'end_timestamp' => $this->end_timestamp?->toIso8601String(),
            'user_sentiment' => $this->user_sentiment,
            'call_successful' => $this->call_successful,
            'transcript' => $this->transcript,
            'recording_url' => $this->recording_url,
            'cost' => $this->cost,

            // Dados enriquecidos
            'local_data' => $this->temDadosLocais() ? [
                'contato_id' => $this->contato_id,
                'cliente_nome' => $this->cliente_nome,
                'empresa_credora' => $this->empresa_credora,
                'valor_devido' => $this->valor_devido,
                'vencimento' => $this->vencimento?->format('d/m/Y'),
                'status_local' => $this->status_local,
                'tentativas' => $this->tentativas,
            ] : null,

            // Dados formatados
            'enhanced_data' => [
                'sentiment' => $this->user_sentiment,
                'duration_formatted' => $this->duration_formatted,
                'cost_formatted' => $this->cost_formatted,
                'transcript_preview' => $this->transcript_preview,
            ],

            // Análise
            'call_analysis' => [
                'call_summary' => [
                    'summary' => $this->call_summary,
                    'duration_ms' => $this->duration_ms,
                ],
            ],

            // Metadata
            'metadata' => $this->metadata,
        ];
    }

    // ============================================
    // MÉTODOS ESTÁTICOS
    // ============================================

    /**
     * Busca ou cria por call_id da Retell
     */
    public static function buscarOuCriarPorRetellId(string $callIdRetell): self
    {
        return static::firstOrCreate(
            ['call_id_retell' => $callIdRetell],
            ['needs_resync' => true]
        );
    }

    /**
     * Estatísticas rápidas - OTIMIZADO
     *
     * Executa uma única query e processa os dados em memória
     * para evitar N+1 queries e melhorar performance
     */
    public static function estatisticas(int $dias = 30): array
    {
        // Buscar todos os dados em uma única query
        $calls = static::ultimosDias($dias)
            ->select([
                'call_successful',
                'call_status',
                'call_type',
                'user_sentiment',
                'direction',
                'duration_ms',
                'cost'
            ])
            ->get();

        // Se não há chamadas, retornar estatísticas vazias
        if ($calls->isEmpty()) {
            return [
                'total_calls' => 0,
                'successful_calls' => 0,
                'failed_calls' => 0,
                'success_rate' => 0,
                'total_duration' => 0,
                'total_duration_formatted' => '0s',
                'average_duration' => 0,
                'average_duration_formatted' => '0s',
                'total_cost' => 0,
                'total_cost_formatted' => 'R$ 0,00',
                'average_cost' => 0,
                'by_status' => [],
                'by_type' => [],
                'by_sentiment' => [],
                'by_direction' => [],
            ];
        }

        // Calcular estatísticas usando Collection methods
        $totalCalls = $calls->count();
        $successfulCalls = $calls->where('call_successful', true)->count();
        $failedCalls = $calls->where('call_successful', false)->count();
        $totalDuration = $calls->sum('duration_ms');
        $avgDuration = $calls->avg('duration_ms');
        $totalCost = $calls->sum('cost');
        $avgCost = $calls->avg('cost');

        // Agrupar por diferentes dimensões
        $byStatus = $calls->groupBy('call_status')
            ->map->count()
            ->toArray();

        $byType = $calls->groupBy('call_type')
            ->map->count()
            ->toArray();

        $bySentiment = $calls->groupBy('user_sentiment')
            ->map(function ($group) use ($totalCalls) {
                $count = $group->count();
                return [
                    'count' => $count,
                    'percentage' => round(($count / $totalCalls) * 100, 2),
                ];
            })
            ->toArray();

        $byDirection = $calls->groupBy('direction')
            ->map->count()
            ->toArray();

        return [
            // Totais gerais
            'total_calls' => $totalCalls,
            'successful_calls' => $successfulCalls,
            'failed_calls' => $failedCalls,
            'success_rate' => $totalCalls > 0
                ? round(($successfulCalls / $totalCalls) * 100, 2)
                : 0,

            // Duração
            'total_duration' => $totalDuration,
            'total_duration_formatted' => static::formatarDuracao($totalDuration),
            'average_duration' => round($avgDuration),
            'average_duration_formatted' => static::formatarDuracao($avgDuration),

            // Custo
            'total_cost' => round($totalCost, 2),
            'total_cost_formatted' => 'R$ ' . number_format($totalCost, 2, ',', '.'),
            'average_cost' => round($avgCost, 2),

            // Agrupamentos
            'by_status' => $byStatus,
            'by_type' => $byType,
            'by_sentiment' => $bySentiment,
            'by_direction' => $byDirection,
        ];
    }

    /**
     * Formata duração de milissegundos para formato legível
     */
    private static function formatarDuracao($ms): string
    {
        if (!$ms || $ms <= 0) {
            return '0s';
        }

        $seconds = floor($ms / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        if ($hours > 0) {
            $minutes = $minutes % 60;
            $seconds = $seconds % 60;
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            $seconds = $seconds % 60;
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }
}

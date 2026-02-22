<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BelongsToEmpresa;

class IntencaoScript extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'intencoes_script';

    protected $fillable = [
        'uuid',
        'script_id',
        'nome',
        'descricao',
        'palavras_chave',
        'regex_patterns',
        'acao',
        'parametros_acao',
        'confianca_minima',
        'prioridade',
        'fallback_intencao_id',
        'criado_por',
        'empresa_id',
    ];

    protected $casts = [
        'palavras_chave' => 'array',
        'regex_patterns' => 'array',
        'parametros_acao' => 'array',
        'confianca_minima' => 'decimal:2',
        'prioridade' => 'integer',
        'script_id' => 'integer',
        'fallback_intencao_id' => 'integer',
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
     * Script ao qual esta intenção pertence
     */
    public function script()
    {
        return $this->belongsTo(Script::class, 'script_id');
    }

    /**
     * Intenção de fallback
     */
    public function fallback()
    {
        return $this->belongsTo(IntencaoScript::class, 'fallback_intencao_id');
    }

    // Métodos auxiliares

    /**
     * Detecta se o texto contém esta intenção
     */
    public function detectarEmTexto(string $texto, float $confianca = null): bool
    {
        $texto = strtolower($texto);

        // Verifica palavras-chave
        foreach ($this->palavras_chave as $palavra) {
            if (strpos($texto, strtolower($palavra)) !== false) {
                return true;
            }
        }

        // Verifica regex patterns
        if (!empty($this->regex_patterns)) {
            foreach ($this->regex_patterns as $pattern) {
                if (preg_match($pattern, $texto)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Executa a ação definida para esta intenção
     */
    public function executarAcao(array $contexto = []): array
    {
        $parametros = array_merge(
            $this->parametros_acao ?? [],
            $contexto
        );

        return [
            'acao' => $this->acao,
            'parametros' => $parametros,
            'intencao_id' => $this->id,
            'intencao_nome' => $this->nome,
        ];
    }

    // Scopes

    /**
     * Ordenar por prioridade (maior primeiro)
     */
    public function scopeOrdenadoPorPrioridade($query)
    {
        return $query->orderBy('prioridade', 'desc');
    }

    /**
     * Por script
     */
    public function scopePorScript($query, int $scriptId)
    {
        return $query->where('script_id', $scriptId);
    }
}

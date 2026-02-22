<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BelongsToEmpresa;

class Script extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'scripts';

    protected $fillable = [
        'uuid',
        'nome',
        'tipo',
        'descricao',
        'agente_id',
        'agente_nome',
        'voz',
        'idioma',
        'velocidade_fala',
        'horario_inicio',
        'horario_fim',
        'dias_semana',
        'excluir_feriados',
        'tentativas_max',
        'intervalo_entre_tentativas',
        'ativo',
        'versao',
        'criado_por',
        'configuracoes',
        'empresa_id',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'configuracoes' => 'array',
        'excluir_feriados' => 'boolean',
        'ativo' => 'boolean',
        'velocidade_fala' => 'decimal:2',
        'horario_inicio' => 'integer',
        'horario_fim' => 'integer',
        'tentativas_max' => 'integer',
        'intervalo_entre_tentativas' => 'integer',
        'versao' => 'integer',
    ];

    protected $hidden = [];

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
     * Intenções associadas ao script
     */
    public function intencoes()
    {
        return $this->hasMany(IntencaoScript::class, 'script_id');
    }

    /**
     * Mailings que usam este script
     */
    public function mailings()
    {
        return $this->hasMany(Mailing::class, 'script_id');
    }

    /**
     * Logs do script
     */
    public function logs()
    {
        return $this->hasMany(ScriptLog::class, 'script_id');
    }

    // Métodos auxiliares

    /**
     * Verifica se o script está ativo
     */
    public function isAtivo(): bool
    {
        return $this->ativo === true;
    }

    /**
     * Verifica se pode fazer ligação no horário especificado
     */
    public function podeDiscarNaHora(\DateTime $dataHora = null): bool
    {
        if ($dataHora === null) {
            $dataHora = new \DateTime();
        }

        $hora = (int) $dataHora->format('H');
        $diaSemana = (int) $dataHora->format('N'); // 1 = segunda, 7 = domingo

        // Verifica hora
        if ($hora < $this->horario_inicio || $hora >= $this->horario_fim) {
            return false;
        }

        // Verifica dia da semana
        if (!empty($this->dias_semana) && !in_array($diaSemana, $this->dias_semana)) {
            return false;
        }

        return true;
    }

    /**
     * Clona o script criando uma nova versão
     */
    public function clonar(string $novoNome = null): self
    {
        $clone = $this->replicate();
        $clone->uuid = (string) Str::uuid();
        $clone->nome = $novoNome ?? ($this->nome . ' (cópia)');
        $clone->versao = 1;
        $clone->save();

        // Clona as intenções também
        foreach ($this->intencoes as $intencao) {
            $intencaoClone = $intencao->replicate();
            $intencaoClone->uuid = (string) Str::uuid();
            $intencaoClone->script_id = $clone->id;
            $intencaoClone->save();
        }

        return $clone;
    }

    // Scopes

    /**
     * Apenas scripts ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scripts por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}

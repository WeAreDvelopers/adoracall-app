<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToEmpresa;

class Lead extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'leads';

    protected $fillable = [
        'nome',
        'sobrenome',
        'telefone',
        'email',
        'produto',
        'origem',
        'observacoes',
        'campanha',
        'tags',
        'status',
        'tentativas',
        'ultima_tentativa',
        'resultado',
        'interesse_nivel',
        'proxima_acao',
        'data_proxima_acao',
        'empresa_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'tentativas' => 'integer',
        'ultima_tentativa' => 'datetime',
        'data_proxima_acao' => 'datetime'
    ];

    /**
     * Relacionamento com ligações de vendas
     */
    public function ligacoesVendas()
    {
        return $this->hasMany(LigacaoVenda::class);
    }

    /**
     * Retorna nome completo do lead
     */
    public function getNomeCompletoAttribute(): string
    {
        return trim($this->nome . ' ' . ($this->sobrenome ?? ''));
    }

    /**
     * Retorna primeiro nome para uso no conversation flow
     */
    public function getPrimeiroNomeAttribute(): string
    {
        return $this->nome;
    }

    /**
     * Verifica se o lead atingiu o limite de tentativas
     */
    public function atingiuLimiteTentativas(int $limite = 5): bool
    {
        return $this->tentativas >= $limite;
    }

    /**
     * Incrementa o contador de tentativas
     */
    public function incrementarTentativas(): void
    {
        $this->tentativas++;
        $this->ultima_tentativa = \Carbon\Carbon::now();
        $this->save();
    }

    /**
     * Adiciona uma tag ao lead
     */
    public function adicionarTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }
    }

    /**
     * Remove uma tag do lead
     */
    public function removerTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        $this->tags = array_values($tags);
        $this->save();
    }

    /**
     * Atualiza o nível de interesse
     */
    public function atualizarInteresse(string $nivel): void
    {
        $niveisValidos = ['frio', 'morno', 'quente', 'muito_quente'];

        if (in_array($nivel, $niveisValidos)) {
            $this->interesse_nivel = $nivel;
            $this->save();
        }
    }
}

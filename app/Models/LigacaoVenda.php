<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToEmpresa;

class LigacaoVenda extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'ligacoes_vendas';

    protected $fillable = [
        'lead_id',
        'sid_twilio',
        'call_id_retell',
        'duracao',
        'status',
        'url_gravacao',
        'resultado',
        'detalhes',
        'interesse_demonstrado',
        'objecoes',
        'proximos_passos',
        'agendamento_follow_up',
        'empresa_id',
    ];

    protected $casts = [
        'detalhes' => 'array',
        'objecoes' => 'array',
        'duracao' => 'integer',
        'interesse_demonstrado' => 'boolean',
        'agendamento_follow_up' => 'datetime'
    ];

    /**
     * Relacionamento com lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Registra interesse demonstrado
     */
    public function registrarInteresse(bool $interessado, array $objecoes = []): void
    {
        $this->interesse_demonstrado = $interessado;
        $this->objecoes = $objecoes;
        $this->save();
    }

    /**
     * Adiciona informação nos detalhes (JSON)
     */
    public function adicionarDetalhe(string $chave, mixed $valor): void
    {
        $detalhes = $this->detalhes ?? [];
        $detalhes[$chave] = $valor;
        $this->detalhes = $detalhes;
        $this->save();
    }

    /**
     * Agenda follow-up
     */
    public function agendarFollowUp(string $data, string $acao): void
    {
        $this->agendamento_follow_up = $data;
        $this->proximos_passos = $acao;
        $this->save();
    }
}

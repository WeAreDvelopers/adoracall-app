<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToEmpresa;

class Acordo extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'contato_id',
        'ligacao_id',
        'proposta_id',
        'aceito_em',
        'confirmacao_texto',
        'transcricao_trecho',
        'valor_acordado',
        'parcelas_acordadas',
        'valor_parcela_acordada',
        'link_pagamento',
        'codigo_boleto',
        'status',
        'user_agent',
        'ip_address',
        'empresa_id',
    ];

    protected $casts = [
        'aceito_em' => 'datetime',
    ];

    public function contato(): BelongsTo
    {
        return $this->belongsTo(Contato::class);
    }

    public function ligacao(): BelongsTo
    {
        return $this->belongsTo(Ligacao::class);
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(PropostaPagamento::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToEmpresa;

class Proposta extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'contato_id',
        'tipo',
        'valor_original',
        'valor_final',
        'desconto_percentual',
        'parcelas',
        'valor_parcela',
        'valor_entrada',
        'descricao_agente',
        'status',
        'expira_em',
        'empresa_id',
    ];

    protected $casts = [
        'expira_em' => 'datetime',
        'valor_original' => 'decimal:2',
        'valor_final' => 'decimal:2',
        'desconto_percentual' => 'decimal:2',
        'valor_parcela' => 'decimal:2',
        'valor_entrada' => 'decimal:2',
    ];

    public function contato(): BelongsTo
    {
        return $this->belongsTo(Contato::class);
    }

    public function acordos(): HasMany
    {
        return $this->hasMany(Acordo::class);
    }

    public function isValida(): bool
    {
        return $this->status === 'ativa' &&
               ($this->expira_em === null || $this->expira_em->isFuture());
    }
}

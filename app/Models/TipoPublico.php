<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BelongsToEmpresa;

class TipoPublico extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'tipos_publico';

    protected $fillable = [
        'empresa_id',
        'slug',
        'nome',
        'subtitulo',
        'descricao',
        'cor',
        'icone',
        'max_tentativas',
        'dias_estimados',
        'velocidade_contatos_hora',
        'intervalo_retry',
        'prioridade',
        'faixa_velocidade',
        'insight',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'max_tentativas'           => 'integer',
        'dias_estimados'           => 'integer',
        'velocidade_contatos_hora' => 'integer',
        'intervalo_retry'          => 'integer',
        'ordem'                    => 'integer',
        'ativo'                    => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->nome, '_');
            }
        });
    }

    // Relacionamentos

    public function mailings()
    {
        return $this->hasMany(Mailing::class, 'tipo_publico', 'slug');
    }

    // Scopes

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }
}

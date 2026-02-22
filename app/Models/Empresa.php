<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Empresa extends Model
{
    use SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'uuid',
        'nome',
        'slug',
        'cnpj',
        'email',
        'telefone',
        'endereco',
        'logo_url',
        'ativa',
        'configuracoes',
        'max_usuarios',
    ];

    protected $casts = [
        'ativa' => 'boolean',
        'configuracoes' => 'array',
        'max_usuarios' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->nome);
            }
        });
    }

    // Relacionamentos

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function mailings()
    {
        return $this->hasMany(Mailing::class);
    }

    public function contatos()
    {
        return $this->hasMany(Contato::class);
    }

    public function scripts()
    {
        return $this->hasMany(Script::class);
    }

    public function ligacoes()
    {
        return $this->hasMany(Ligacao::class);
    }

    public function acordos()
    {
        return $this->hasMany(Acordo::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function configuracao()
    {
        return $this->hasOne(EmpresaConfiguracao::class);
    }

    public function integracao()
    {
        return $this->hasOne(EmpresaIntegracao::class);
    }

    // Accessors

    public function getNomeCredoraAttribute(): string
    {
        return $this->configuracao?->nome_credora ?? $this->nome;
    }

    // Scopes

    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }
}

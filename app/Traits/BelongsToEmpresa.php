<?php

namespace App\Traits;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa()
    {
        // Global Scope: filtra automaticamente por empresa_id
        // NÃO aplica em contexto CLI (artisan, workers) nem quando empresa_id não está bindado
        static::addGlobalScope('empresa', function (Builder $builder) {
            if (!app()->runningInConsole() && app()->bound('empresa_id')) {
                $builder->where($builder->getModel()->getTable() . '.empresa_id', app('empresa_id'));
            }
        });

        // Auto-set empresa_id ao criar registro
        static::creating(function ($model) {
            if (empty($model->empresa_id) && app()->bound('empresa_id')) {
                $model->empresa_id = app('empresa_id');
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}

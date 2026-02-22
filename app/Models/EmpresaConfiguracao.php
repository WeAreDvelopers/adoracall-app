<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaConfiguracao extends Model
{
    protected $table = 'empresa_configuracoes';

    protected $fillable = [
        'empresa_id',
        'nome_credora',
        'percentual_desconto_alto',
        'percentual_desconto_baixo',
        'limite_valor_desconto_alto',
        'max_parcelas',
        'valor_minimo_parcela',
    ];

    protected $casts = [
        'percentual_desconto_alto'   => 'decimal:2',
        'percentual_desconto_baixo'  => 'decimal:2',
        'limite_valor_desconto_alto' => 'decimal:2',
        'max_parcelas'               => 'integer',
        'valor_minimo_parcela'       => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Retorna configuração da empresa com defaults garantidos.
     */
    public static function getForEmpresa(?int $empresaId): self
    {
        if ($empresaId) {
            $config = static::where('empresa_id', $empresaId)->first();
            if ($config) {
                return $config;
            }
        }

        return new static([
            'percentual_desconto_alto'   => 10,
            'percentual_desconto_baixo'  => 5,
            'limite_valor_desconto_alto'  => 500,
            'max_parcelas'               => 3,
            'valor_minimo_parcela'       => 100,
        ]);
    }
}

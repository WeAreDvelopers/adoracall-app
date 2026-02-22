<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaIntegracao extends Model
{
    protected $table = 'empresa_integracoes';

    protected $fillable = [
        'empresa_id',
        'retell_api_key',
        'retell_agent_id',
        'retell_agent_id_sales',
        'retell_webhook_secret',
        'retell_from_number',
    ];

    protected $hidden = [
        'retell_api_key',
        'retell_webhook_secret',
    ];

    /**
     * Campos armazenados criptografados.
     */
    private static array $camposEncriptados = [
        'retell_api_key',
        'retell_webhook_secret',
    ];

    /**
     * Mapeamento: nome da coluna DB → nome da chave na API frontend.
     */
    public const FIELD_MAP = [
        'retell_api_key'        => 'integracao_retell_api_key',
        'retell_agent_id'       => 'integracao_retell_agent_id',
        'retell_agent_id_sales' => 'integracao_retell_agent_id_sales',
        'retell_webhook_secret' => 'integracao_retell_webhook_secret',
        'retell_from_number'    => 'integracao_retell_from_number',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public static function isEncrypted(string $field): bool
    {
        return in_array($field, self::$camposEncriptados);
    }

    public static function getCamposEncriptados(): array
    {
        return self::$camposEncriptados;
    }

    /**
     * Retorna nomes das colunas DB de integração.
     */
    public static function getDbColumns(): array
    {
        return array_keys(self::FIELD_MAP);
    }

    /**
     * Converte chaves da API frontend para nomes de coluna DB.
     * Ex: 'integracao_retell_api_key' → 'retell_api_key'
     */
    public static function mapApiToDb(array $apiData): array
    {
        $reversed = array_flip(self::FIELD_MAP);
        $result = [];

        foreach ($apiData as $apiKey => $valor) {
            $dbCol = $reversed[$apiKey] ?? null;
            if ($dbCol) {
                $result[$dbCol] = $valor;
            }
        }

        return $result;
    }
}

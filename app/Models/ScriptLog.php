<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToEmpresa;

class ScriptLog extends Model
{
    use BelongsToEmpresa;

    protected $table = 'script_logs';

    public $timestamps = false;

    protected $fillable = [
        'script_id',
        'mailing_id',
        'contato_id',
        'tipo_evento',
        'detalhes',
        'usuario_id',
        'endereco_ip',
        'empresa_id',
    ];

    protected $casts = [
        'detalhes' => 'array',
        'script_id' => 'integer',
        'mailing_id' => 'integer',
        'contato_id' => 'integer',
        'created_at' => 'datetime',
    ];

    // Relacionamentos

    /**
     * Script associado ao log
     */
    public function script()
    {
        return $this->belongsTo(Script::class, 'script_id');
    }

    /**
     * Mailing associado ao log (opcional)
     */
    public function mailing()
    {
        return $this->belongsTo(Mailing::class, 'mailing_id');
    }

    /**
     * Contato associado ao log (opcional)
     */
    public function contato()
    {
        return $this->belongsTo(Contato::class, 'contato_id');
    }

    // Métodos estáticos para facilitar logging

    /**
     * Log de criação de script
     */
    public static function scriptCriado(Script $script, string $usuarioId = null, string $ip = null): self
    {
        return self::create([
            'script_id' => $script->id,
            'tipo_evento' => 'script_criado',
            'detalhes' => [
                'nome' => $script->nome,
                'tipo' => $script->tipo,
            ],
            'usuario_id' => $usuarioId,
            'endereco_ip' => $ip,
        ]);
    }

    /**
     * Log de edição de script
     */
    public static function scriptEditado(Script $script, array $alteracoes, string $usuarioId = null, string $ip = null): self
    {
        return self::create([
            'script_id' => $script->id,
            'tipo_evento' => 'script_editado',
            'detalhes' => [
                'alteracoes' => $alteracoes,
            ],
            'usuario_id' => $usuarioId,
            'endereco_ip' => $ip,
        ]);
    }

    /**
     * Log de início de mailing
     */
    public static function mailingIniciado(Mailing $mailing, string $usuarioId = null, string $ip = null): self
    {
        return self::create([
            'script_id' => $mailing->script_id,
            'mailing_id' => $mailing->id,
            'tipo_evento' => 'mailing_iniciado',
            'detalhes' => [
                'nome' => $mailing->nome,
                'total_contatos' => $mailing->total_contatos,
            ],
            'usuario_id' => $usuarioId,
            'endereco_ip' => $ip,
        ]);
    }

    /**
     * Log de pausa de mailing
     */
    public static function mailingPausado(Mailing $mailing, string $usuarioId = null, string $ip = null): self
    {
        return self::create([
            'script_id' => $mailing->script_id,
            'mailing_id' => $mailing->id,
            'tipo_evento' => 'mailing_pausado',
            'detalhes' => [
                'processados' => $mailing->processados,
                'total' => $mailing->total_contatos,
            ],
            'usuario_id' => $usuarioId,
            'endereco_ip' => $ip,
        ]);
    }

    // Scopes

    /**
     * Logs de um script específico
     */
    public function scopePorScript($query, int $scriptId)
    {
        return $query->where('script_id', $scriptId);
    }

    /**
     * Logs de um mailing específico
     */
    public function scopePorMailing($query, int $mailingId)
    {
        return $query->where('mailing_id', $mailingId);
    }

    /**
     * Logs por tipo de evento
     */
    public function scopePorTipoEvento($query, string $tipoEvento)
    {
        return $query->where('tipo_evento', $tipoEvento);
    }

    /**
     * Logs recentes primeiro
     */
    public function scopeRecentes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}

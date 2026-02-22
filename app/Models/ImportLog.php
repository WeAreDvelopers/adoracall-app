<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToEmpresa;

class ImportLog extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'mailing_id',
        'arquivo_nome',
        'linha_numero',
        'status',
        'dados_originais',
        'dados_processados',
        'mensagem',
        'erros_validacao',
        'acao_tomada',
        'contato_id',
        'tempo_processamento_ms',
        'ip_address',
        'usuario_id',
        'empresa_id',
    ];

    protected $casts = [
        'dados_originais' => 'array',
        'dados_processados' => 'array',
        'erros_validacao' => 'array',
        'tempo_processamento_ms' => 'integer',
        'linha_numero' => 'integer',
    ];

    // Status possíveis
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_WARNING = 'warning';
    const STATUS_SKIPPED = 'skipped';

    // Ações tomadas
    const ACAO_INSERTED = 'inserted';
    const ACAO_UPDATED = 'updated';
    const ACAO_SKIPPED = 'skipped';
    const ACAO_REJECTED = 'rejected';
    const ACAO_DUPLICATED = 'duplicated';

    /**
     * Relacionamentos
     */
    public function mailing()
    {
        return $this->belongsTo(Mailing::class);
    }

    public function contato()
    {
        return $this->belongsTo(Contato::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Scopes para facilitar queries
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeErrors($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    public function scopeWarnings($query)
    {
        return $query->where('status', self::STATUS_WARNING);
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', self::STATUS_SKIPPED);
    }

    public function scopeByArquivo($query, $arquivo)
    {
        return $query->where('arquivo_nome', $arquivo);
    }

    /**
     * Métodos estáticos para criar logs
     */
    public static function logSuccess($mailing_id, $linha_numero, $dados, $contato_id, $tempo_ms = null)
    {
        return self::create([
            'mailing_id' => $mailing_id,
            'arquivo_nome' => request()->file('file')?->getClientOriginalName() ?? 'import.csv',
            'linha_numero' => $linha_numero,
            'status' => self::STATUS_SUCCESS,
            'dados_originais' => $dados,
            'dados_processados' => $dados,
            'mensagem' => 'Contato importado com sucesso',
            'acao_tomada' => self::ACAO_INSERTED,
            'contato_id' => $contato_id,
            'tempo_processamento_ms' => $tempo_ms,
            'ip_address' => request()->ip(),
            'usuario_id' => auth()->id(),
        ]);
    }

    public static function logError($mailing_id, $linha_numero, $dados, $mensagem, $erros = [])
    {
        return self::create([
            'mailing_id' => $mailing_id,
            'arquivo_nome' => request()->file('file')?->getClientOriginalName() ?? 'import.csv',
            'linha_numero' => $linha_numero,
            'status' => self::STATUS_ERROR,
            'dados_originais' => $dados,
            'mensagem' => $mensagem,
            'erros_validacao' => $erros,
            'acao_tomada' => self::ACAO_REJECTED,
            'ip_address' => request()->ip(),
            'usuario_id' => auth()->id(),
        ]);
    }

    public static function logWarning($mailing_id, $linha_numero, $dados, $mensagem, $contato_id = null)
    {
        return self::create([
            'mailing_id' => $mailing_id,
            'arquivo_nome' => request()->file('file')?->getClientOriginalName() ?? 'import.csv',
            'linha_numero' => $linha_numero,
            'status' => self::STATUS_WARNING,
            'dados_originais' => $dados,
            'mensagem' => $mensagem,
            'acao_tomada' => $contato_id ? self::ACAO_INSERTED : self::ACAO_SKIPPED,
            'contato_id' => $contato_id,
            'ip_address' => request()->ip(),
            'usuario_id' => auth()->id(),
        ]);
    }

    public static function logDuplicated($mailing_id, $linha_numero, $dados, $contato_existente_id)
    {
        return self::create([
            'mailing_id' => $mailing_id,
            'arquivo_nome' => request()->file('file')?->getClientOriginalName() ?? 'import.csv',
            'linha_numero' => $linha_numero,
            'status' => self::STATUS_SKIPPED,
            'dados_originais' => $dados,
            'mensagem' => 'Contato duplicado - telefone já existe no mailing',
            'acao_tomada' => self::ACAO_DUPLICATED,
            'contato_id' => $contato_existente_id,
            'ip_address' => request()->ip(),
            'usuario_id' => auth()->id(),
        ]);
    }

    public static function logSkipped($mailing_id, $linha_numero, $dados, $motivo)
    {
        return self::create([
            'mailing_id' => $mailing_id,
            'arquivo_nome' => request()->file('file')?->getClientOriginalName() ?? 'import.csv',
            'linha_numero' => $linha_numero,
            'status' => self::STATUS_SKIPPED,
            'dados_originais' => $dados,
            'mensagem' => $motivo,
            'acao_tomada' => self::ACAO_SKIPPED,
            'ip_address' => request()->ip(),
            'usuario_id' => auth()->id(),
        ]);
    }

    /**
     * Estatísticas de importação
     */
    public static function estatisticasPorMailing($mailing_id, $arquivo = null)
    {
        $query = self::where('mailing_id', $mailing_id);

        if ($arquivo) {
            $query->where('arquivo_nome', $arquivo);
        }

        return [
            'total_linhas' => $query->count(),
            'sucesso' => $query->where('status', self::STATUS_SUCCESS)->count(),
            'erros' => $query->where('status', self::STATUS_ERROR)->count(),
            'avisos' => $query->where('status', self::STATUS_WARNING)->count(),
            'ignoradas' => $query->where('status', self::STATUS_SKIPPED)->count(),
            'duplicadas' => $query->where('acao_tomada', self::ACAO_DUPLICATED)->count(),
            'tempo_medio_ms' => $query->avg('tempo_processamento_ms'),
            'tempo_total_ms' => $query->sum('tempo_processamento_ms'),
        ];
    }

    /**
     * Obter erros agrupados
     */
    public static function errosAgrupados($mailing_id)
    {
        return self::where('mailing_id', $mailing_id)
            ->where('status', self::STATUS_ERROR)
            ->get()
            ->groupBy('mensagem')
            ->map(function ($group) {
                return [
                    'mensagem' => $group->first()->mensagem,
                    'ocorrencias' => $group->count(),
                    'linhas' => $group->pluck('linha_numero')->toArray(),
                ];
            })
            ->values();
    }
}

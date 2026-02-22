<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToEmpresa;

class Contato extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'contatos';

    protected $fillable = [
        'nome',
        'telefone',
        'valor_debito',
        'vencimento',
        'campanha',
        'campanha_id',
        'mailing_id',
        'status',
        'tentativas',
        'resultado',
        'ultima_ligacao',
        'sobrenome',
        'cpf',
        'cpf_primeiros_digitos',
        'data_nascimento',
        'empresa_credora',
        'ultima_tentativa',
        'historico_inadimplencia',
        'tentativas_contato',
        'empresa_id',
    ];

    protected $casts = [
        'valor_debito'       => 'float',
        'vencimento'         => 'date',
        'tentativas'         => 'integer',
        'campanha_id'        => 'integer',
        'mailing_id'         => 'integer',
        'ultima_ligacao'     => 'datetime',
        'data_nascimento'    => 'date',
        'ultima_tentativa'   => 'datetime',
        'tentativas_contato' => 'integer',
    ];

    /**
     * Atributos que devem ser ocultados em arrays/JSON
     * IMPORTANTE: CPF criptografado nunca deve ser exposto
     */
    // protected $hidden = [
    //     'cpf',
    // ];

    /**
     * Mailing ao qual este contato pertence
     */
    public function mailing()
    {
        return $this->belongsTo(Mailing::class, 'mailing_id');
    }

    /**
     * Ligações feitas para este contato
     */
    public function ligacoes()
    {
        return $this->hasMany(Ligacao::class, 'contato_id');
    }

    /**
     * Jobs de fila para este contato
     */
    public function queueJobs()
    {
        return $this->hasMany(QueueJob::class, 'contato_id');
    }

    /**
     * Agendamentos deste contato
     */
    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class, 'contato_id');
    }

    /**
     * Propostas de pagamento deste contato
     */
    public function propostas()
    {
        return $this->hasMany(PropostaPagamento::class, 'contato_id');
    }

    /**
     * Última proposta de pagamento
     */
    public function ultimaProposta()
    {
        return $this->hasOne(PropostaPagamento::class, 'contato_id')->latest();
    }

    // Métodos auxiliares

    /**
     * Incrementa tentativas de contato
     */
    public function incrementarTentativas(): bool
    {
        $this->tentativas       += 1;
        $this->ultima_tentativa  = Carbon::now();
        return $this->save();
    }

    /**
     * Verifica se atingiu limite de tentativas
     */
    public function atingiuLimiteTentativas(int $maxTentativas = 3): bool
    {
        return $this->tentativas >= $maxTentativas;
    }

    // Scopes

    /**
     * Contatos de um mailing específico
     */
    public function scopePorMailing($query, int $mailingId)
    {
        return $query->where('mailing_id', $mailingId);
    }

    /**
     * Contatos por status
     */
    public function scopePorStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Retorna nome completo do contato
     */
    public function getNomeCompletoAttribute(): string
    {
        return trim($this->nome . ' ' . ($this->sobrenome ?? ''));
    }

    /**
     * Retorna primeiro nome para uso no conversation flow
     */
    public function getPrimeiroNomeAttribute(): string
    {
        return $this->nome;
    }
}

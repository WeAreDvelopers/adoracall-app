<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use App\Traits\BelongsToEmpresa;

class Ligacao extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'ligacoes';

    protected $fillable = [
        'contato_id',
        'sid_twilio',
        'call_id_retell',
        'duracao',
        'status',
        'url_gravacao',
        'resultado',
        'detalhes',
        'tentativas_validacao',
        'validacao_sucesso',
        'validacao_timestamp',
        'cpf_informado',
        'data_nascimento_informada',
        'empresa_id',
    ];

    protected $casts = [
        'detalhes'                  => 'array',
        'duracao'                   => 'integer',
        'tentativas_validacao'      => 'integer',
        'validacao_sucesso'         => 'boolean',
        'validacao_timestamp'       => 'datetime',
        'data_nascimento_informada' => 'date',
    ];

    /**
     * Relacionamento com contato
     */
    public function contato()
    {
        return $this->belongsTo(Contato::class);
    }

    /**
     * Relacionamento com histórico de chamada do Retell
     */
    public function callHistory()
    {
        return $this->hasOne(CallHistory::class, 'call_id_retell', 'call_id_retell');
    }

    /**
     * Incrementa tentativas de validação
     */
    public function incrementarTentativasValidacao(): void
    {
        $this->tentativas_validacao++;
        $this->validacao_timestamp = Carbon::now();
        $this->save();
    }

    /**
     * Registra validação bem-sucedida
     */
    public function registrarValidacaoSucesso(string $cpfInformado, string $dataNascimentoInformada): void
    {
        $this->validacao_sucesso         = true;
        $this->cpf_informado             = $cpfInformado;
        $this->data_nascimento_informada = $dataNascimentoInformada;
        $this->validacao_timestamp       = Carbon::now();
        $this->save();
    }

    /**
     * Registra validação falha
     */
    public function registrarValidacaoFalha(string $cpfInformado, string $dataNascimentoInformada): void
    {
        $this->validacao_sucesso         = false;
        $this->cpf_informado             = $cpfInformado;
        $this->data_nascimento_informada = $dataNascimentoInformada;
        $this->incrementarTentativasValidacao();
    }

    /**
     * Verifica se atingiu limite de tentativas de validação
     */
    public function atingiuLimiteValidacao(int $limite = 3): bool
    {
        return $this->tentativas_validacao >= $limite;
    }

    /**
     * Adiciona informação nos detalhes (JSON)
     */
    public function adicionarDetalhe(string $chave, mixed $valor): void
    {
        $detalhes         = $this->detalhes ?? [];
        $detalhes[$chave] = $valor;
        $this->detalhes   = $detalhes;
        $this->save();
    }
}

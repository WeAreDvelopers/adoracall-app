<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EmpresaConfiguracao;
use App\Models\EmpresaIntegracao;
use App\Services\ApiResponseService;
use App\Services\IntegracaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class ConfiguracaoController extends Controller
{
    // ========== VIEW ==========

    public function index()
    {
        return view('configuracoes.index');
    }

    // ========== API ==========

    public function getConfiguracoes(Request $request)
    {
        if (!app()->bound('empresa_id')) {
            return ApiResponseService::forbidden('Empresa não identificada');
        }

        $empresa = Empresa::withoutGlobalScopes()->find(app('empresa_id'));

        if (!$empresa) {
            return ApiResponseService::notFound('Empresa não encontrada');
        }

        $configuracao = EmpresaConfiguracao::getForEmpresa($empresa->id);
        $integracao = $empresa->integracao;
        $integracoesMascaradas = IntegracaoService::maskCredentials($integracao);

        return ApiResponseService::success([
            'empresa' => [
                'id'             => $empresa->id,
                'nome'           => $empresa->nome,
                'cnpj'           => $empresa->cnpj,
                'email'          => $empresa->email,
                'telefone'       => $empresa->telefone,
                'endereco'       => $empresa->endereco,
                'logo_url'       => $empresa->logo_url,
                'configuracoes'  => [
                    'nome_credora'               => $configuracao->nome_credora,
                    'percentual_desconto_alto'   => $configuracao->percentual_desconto_alto,
                    'percentual_desconto_baixo'  => $configuracao->percentual_desconto_baixo,
                    'limite_valor_desconto_alto'  => $configuracao->limite_valor_desconto_alto,
                    'max_parcelas'               => $configuracao->max_parcelas,
                    'valor_minimo_parcela'       => $configuracao->valor_minimo_parcela,
                ],
                'integracoes'    => $integracoesMascaradas,
                'has_own_credentials' => IntegracaoService::hasOwnCredentials($empresa->id),
            ],
        ]);
    }

    public function updateConfiguracoes(Request $request)
    {
        if (!app()->bound('empresa_id')) {
            return ApiResponseService::forbidden('Empresa não identificada');
        }

        $empresa = Empresa::withoutGlobalScopes()->find(app('empresa_id'));

        if (!$empresa) {
            return ApiResponseService::notFound('Empresa não encontrada');
        }

        $validator = Validator::make($request->all(), [
            // Perfil da empresa
            'nome'           => 'sometimes|string|max:255',
            'cnpj'           => 'nullable|string|max:18',
            'email'          => 'nullable|email|max:255',
            'telefone'       => 'nullable|string|max:20',
            'endereco'       => 'nullable|string|max:500',
            'logo_url'       => 'nullable|url|max:500',

            // Configurações (termos da fila)
            'configuracoes'                              => 'sometimes|array',
            'configuracoes.nome_credora'                 => 'nullable|string|max:255',
            'configuracoes.percentual_desconto_alto'     => 'nullable|numeric|min:0|max:100',
            'configuracoes.percentual_desconto_baixo'    => 'nullable|numeric|min:0|max:100',
            'configuracoes.limite_valor_desconto_alto'   => 'nullable|numeric|min:0',
            'configuracoes.max_parcelas'                 => 'nullable|integer|min:1|max:60',
            'configuracoes.valor_minimo_parcela'         => 'nullable|numeric|min:0',

            // Integrações (Retell AI)
            'integracoes'                                       => 'sometimes|array',
            'integracoes.integracao_retell_api_key'             => 'nullable|string|max:500',
            'integracoes.integracao_retell_agent_id'            => 'nullable|string|max:255',
            'integracoes.integracao_retell_agent_id_sales'      => 'nullable|string|max:255',
            'integracoes.integracao_retell_webhook_secret'      => 'nullable|string|max:500',
            'integracoes.integracao_retell_from_number'         => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        // Atualizar perfil da empresa
        $camposPerfil = ['nome', 'cnpj', 'email', 'telefone', 'endereco', 'logo_url'];
        foreach ($camposPerfil as $campo) {
            if ($request->has($campo)) {
                $empresa->{$campo} = $request->input($campo);
            }
        }
        $empresa->save();

        // Atualizar configurações (termos da fila) na tabela empresa_configuracoes
        if ($request->has('configuracoes')) {
            $novasConfig = $request->input('configuracoes');
            EmpresaConfiguracao::updateOrCreate(
                ['empresa_id' => $empresa->id],
                $novasConfig
            );
        }

        // Atualizar integrações na tabela empresa_integracoes
        if ($request->has('integracoes')) {
            $integracoes = $request->input('integracoes');

            // Não sobrescrever com valores mascarados (contêm •) ou vazios
            foreach ($integracoes as $chave => $valor) {
                if ($valor === null || $valor === '' || strpos($valor, '•') !== false) {
                    unset($integracoes[$chave]);
                }
            }

            if (!empty($integracoes)) {
                // Converter chaves API → colunas DB
                $dbData = EmpresaIntegracao::mapApiToDb($integracoes);

                // Criptografar campos sensíveis
                $dbData = IntegracaoService::encryptCredentials($dbData);

                $integracao = EmpresaIntegracao::firstOrNew(['empresa_id' => $empresa->id]);
                foreach ($dbData as $col => $val) {
                    $integracao->{$col} = $val;
                }
                $integracao->empresa_id = $empresa->id;
                $integracao->save();
            }
        }

        // Recarregar relationships
        $empresa->load(['configuracao', 'integracao']);

        $configuracao = $empresa->configuracao ?? EmpresaConfiguracao::getForEmpresa($empresa->id);
        $integracoesMascaradas = IntegracaoService::maskCredentials($empresa->integracao);

        return ApiResponseService::success([
            'empresa' => [
                'id'             => $empresa->id,
                'nome'           => $empresa->nome,
                'cnpj'           => $empresa->cnpj,
                'email'          => $empresa->email,
                'telefone'       => $empresa->telefone,
                'endereco'       => $empresa->endereco,
                'logo_url'       => $empresa->logo_url,
                'configuracoes'  => [
                    'nome_credora'               => $configuracao->nome_credora,
                    'percentual_desconto_alto'   => $configuracao->percentual_desconto_alto,
                    'percentual_desconto_baixo'  => $configuracao->percentual_desconto_baixo,
                    'limite_valor_desconto_alto'  => $configuracao->limite_valor_desconto_alto,
                    'max_parcelas'               => $configuracao->max_parcelas,
                    'valor_minimo_parcela'       => $configuracao->valor_minimo_parcela,
                ],
                'integracoes'    => $integracoesMascaradas,
                'has_own_credentials' => IntegracaoService::hasOwnCredentials($empresa->id),
            ],
        ], 'Configurações atualizadas com sucesso');
    }
}

<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScriptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $empresa = DB::table('empresas')->where('slug', 'empresa-padrao')->first();

        if (!$empresa) {
            $this->command->warn('Empresa padrão não encontrada. Pulando ScriptSeeder.');
            return;
        }

        $scripts = [
            [
                'nome'      => 'Script de Cobrança Padrão',
                'tipo'      => 'cobranca',
                'descricao' => 'Script padrão para campanhas de cobrança',
            ],
            [
                'nome'      => 'Script de Vendas Padrão',
                'tipo'      => 'vendas',
                'descricao' => 'Script padrão para campanhas de vendas',
            ],
        ];

        foreach ($scripts as $script) {
            if (DB::table('scripts')->where('nome', $script['nome'])->exists()) {
                continue;
            }

            DB::table('scripts')->insert(array_merge($script, [
                'uuid'                       => Str::uuid(),
                'empresa_id'                 => $empresa->id,
                'agente_id'                  => env('RETELL_AGENT_ID', 'agent_default'),
                'agente_nome'                => 'Angélica',
                'voz'                        => 'feminine',
                'idioma'                     => 'pt-BR',
                'velocidade_fala'            => 1.0,
                'horario_inicio'             => 8,
                'horario_fim'                => 20,
                'dias_semana'                => json_encode([1, 2, 3, 4, 5]),
                'excluir_feriados'           => true,
                'tentativas_max'             => 3,
                'intervalo_entre_tentativas' => 86400,
                'ativo'                      => true,
                'versao'                     => 1,
                'criado_por'                 => 'sistema',
                'created_at'                 => Carbon::now(),
                'updated_at'                 => Carbon::now(),
            ]));
        }
    }
}

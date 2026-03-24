<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TipoPublicoSeeder extends Seeder
{
    public function run()
    {
        $empresa = DB::table('empresas')->where('slug', 'empresa-padrao')->first();

        if (!$empresa) {
            $this->command->warn('Empresa padrão não encontrada. Pulando TipoPublicoSeeder.');
            return;
        }

        $tipos = [
            [
                'slug'                     => 'atraso_leve',
                'nome'                     => 'Atraso Leve',
                'subtitulo'                => '1 a 15 dias',
                'descricao'                => 'Cliente ainda "quente", maior chance de sucesso.',
                'cor'                      => '#22c55e',
                'icone'                    => 'A',
                'max_tentativas'           => 6,
                'dias_estimados'           => 2,
                'velocidade_contatos_hora' => 50,
                'intervalo_retry'          => 240,
                'prioridade'               => 'alta',
                'faixa_velocidade'         => '40-60',
                'insight'                  => 'Taxa de contato alta. Vale insistir mais com intervalos menores entre tentativas.',
                'ordem'                    => 1,
            ],
            [
                'slug'                     => 'atraso_medio',
                'nome'                     => 'Atraso Médio',
                'subtitulo'                => '16 a 60 dias',
                'descricao'                => 'Objetivo de negociação e acordo.',
                'cor'                      => '#f97316',
                'icone'                    => 'B',
                'max_tentativas'           => 6,
                'dias_estimados'           => 3,
                'velocidade_contatos_hora' => 35,
                'intervalo_retry'          => 360,
                'prioridade'               => 'normal',
                'faixa_velocidade'         => '30-40',
                'insight'                  => 'Menos insistência diária (2-3 tentativas/dia), mas cobertura ampla de horários.',
                'ordem'                    => 2,
            ],
            [
                'slug'                     => 'atraso_alto',
                'nome'                     => 'Atraso Alto',
                'subtitulo'                => '61 a 180 dias',
                'descricao'                => 'Última tentativa automática. Abordagem conservadora.',
                'cor'                      => '#ef4444',
                'icone'                    => 'C',
                'max_tentativas'           => 5,
                'dias_estimados'           => 3,
                'velocidade_contatos_hora' => 25,
                'intervalo_retry'          => 720,
                'prioridade'               => 'normal',
                'faixa_velocidade'         => '20-30',
                'insight'                  => 'Contato mais difícil. Evitar insistência excessiva para não gerar bloqueios.',
                'ordem'                    => 3,
            ],
            [
                'slug'                     => 'inadimplencia_critica',
                'nome'                     => 'Inadimplência Crítica',
                'subtitulo'                => 'Pré-jurídico',
                'descricao'                => 'Comunicação formal e conservadora.',
                'cor'                      => '#b91c1c',
                'icone'                    => 'D',
                'max_tentativas'           => 3,
                'dias_estimados'           => 3,
                'velocidade_contatos_hora' => 20,
                'intervalo_retry'          => 1440,
                'prioridade'               => 'baixa',
                'faixa_velocidade'         => '20',
                'insight'                  => 'Abordagem altamente conservadora. Apenas 1 tentativa por dia. Risco legal elevado.',
                'ordem'                    => 4,
            ],
            [
                'slug'                     => 'leads_novos',
                'nome'                     => 'Leads / Confirmação',
                'subtitulo'                => 'Novos contatos',
                'descricao'                => 'Validação de contato. Taxa alta de resposta.',
                'cor'                      => '#3b82f6',
                'icone'                    => 'E',
                'max_tentativas'           => 4,
                'dias_estimados'           => 2,
                'velocidade_contatos_hora' => 55,
                'intervalo_retry'          => 360,
                'prioridade'               => 'alta',
                'faixa_velocidade'         => '50-60',
                'insight'                  => 'Novo contato = alta receptividade. Focar em validação rápida e confirmação.',
                'ordem'                    => 5,
            ],
        ];

        $now = Carbon::now();

        foreach ($tipos as $tipo) {
            $exists = DB::table('tipos_publico')
                ->where('empresa_id', $empresa->id)
                ->where('slug', $tipo['slug'])
                ->exists();

            if (!$exists) {
                DB::table('tipos_publico')->insert(array_merge($tipo, [
                    'empresa_id' => $empresa->id,
                    'ativo'      => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        $this->command->info('TipoPublicoSeeder: ' . count($tipos) . ' tipos de público criados.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Mailing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContatoController extends Controller
{
    /**
     * Lista contatos importados com filtros e paginação
     * GET /contatos
     */
    public function index(Request $request)
    {
        $query = Contato::with('mailing');

        // Busca por nome ou telefone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('sobrenome', 'like', "%{$search}%")
                  ->orWhere('telefone', 'like', "%{$search}%")
                  ->orWhere('cpf_primeiros_digitos', 'like', "%{$search}%");
            });
        }

        // Filtro por campanha/mailing
        if ($request->filled('mailing_id')) {
            $query->where('mailing_id', $request->mailing_id);
        }

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por resultado
        if ($request->filled('resultado')) {
            $query->where('resultado', $request->resultado);
        }

        // Estatísticas para os cards de resumo (antes de paginar)
        $statsQuery = clone $query;
        $stats = $statsQuery->select(
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes"),
            DB::raw("SUM(CASE WHEN status = 'chamando' THEN 1 ELSE 0 END) as chamando"),
            DB::raw("SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos")
        )->first();

        // Ordenação e paginação
        $contatos = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->query());

        // Mailings para o dropdown de filtro
        $mailings = Mailing::orderBy('nome')->get(['id', 'nome']);

        // Resultados distintos para o dropdown
        $resultados = Contato::whereNotNull('resultado')
            ->where('resultado', '!=', '')
            ->distinct()
            ->pluck('resultado');

        return view('contatos.index', compact('contatos', 'mailings', 'resultados', 'stats'));
    }

    /**
     * Exibe detalhes de um contato com histórico de ligações
     * GET /contatos/{id}
     */
    public function show($id)
    {
        $contato = Contato::with(['mailing', 'ligacoes.callHistory', 'propostas'])
            ->findOrFail($id);

        // Ordenar ligações da mais recente para a mais antiga
        $ligacoes = $contato->ligacoes->sortByDesc('created_at');

        // Estatísticas das ligações
        $ligacoesStats = [
            'total'          => $ligacoes->count(),
            'atendidas'      => $ligacoes->where('foi_atendida', true)->count(),
            'duracao_total'  => $ligacoes->sum('duracao'),
            'duracao_media'  => $ligacoes->count() > 0 ? round($ligacoes->avg('duracao')) : 0,
        ];

        return view('contatos.show', compact('contato', 'ligacoes', 'ligacoesStats'));
    }
}

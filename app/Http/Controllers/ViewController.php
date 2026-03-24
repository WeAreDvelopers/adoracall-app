<?php
namespace App\Http\Controllers;

class ViewController extends Controller
{
    /**
     * Home page - Seleção de tipo de chamada
     */
    public function home()
    {
        return view('pages.home-simple');
    }

    /**
     * Página de cobrança
     */
    public function cobranca()
    {
        return view('pages.cobranca');
    }

    /**
     * Página de vendas
     */
    public function vendas()
    {
        return view('pages.vendas');
    }

    /**
     * Dashboard geral de chamadas
     */
    public function dashboardIndex()
    {
        return view('dashboard.index');
    }

    /**
     * Dashboard de cobrança com métricas
     */
    public function dashboardCobranca()
    {
        return view('dashboard.cobranca');
    }

    /**
     * Dashboard de vendas com métricas
     */
    public function dashboardVendas()
    {
        return view('dashboard.vendas');
    }

    /**
     * Formulário de criação de campanha
     */
    public function criarCampanha()
    {
        return view('campanha.criar');
    }

    /**
     * Página de importação de contatos
     */
    public function importacao()
    {
        return view('campanha.importacao');
    }

    /**
     * Página de status de importações
     */
    public function statusImportacoes()
    {
        return view('campanha.status-importacoes');
    }

    /**
     * Página de status da fila de processamento
     */
    public function statusFila()
    {
        return view('campanha.status-fila');
    }

    /**
     * Página de status de ligações em tempo real
     */
    public function statusLigacoes()
    {
        return view('campanha.status-ligacoes');
    }

    /**
     * Página de controle da fila de ligações
     */
    public function gerenciarFila()
    {
        return view('campanha.gerenciar-fila-v2');
    }

    /**
     * Lista de todas as campanhas
     */
    public function campanhasIndex()
    {
        return view('campanha.campanhas-index');
    }

    /**
     * Visualizar detalhes da campanha com seus contatos
     */
    public function visualizarCampanha($id)
    {
        return view('campanha.visualizar', ['campanhaId' => $id]);
    }

    /**
     * Gerenciamento de tipos de público
     */
    public function tiposPublico()
    {
        return view('campanha.tipos-publico');
    }
}

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campanhas de Vendas - URA Dvelopers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo h1 { font-size: 28px; margin: 0; }
        .logo p { font-size: 12px; opacity: 0.9; margin: 5px 0 0 0; }
        .header-actions { display: flex; gap: 20px; align-items: center; font-size: 14px; }
        .logout-btn { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .container { min-height: calc(100vh - 100px); display: flex; flex-direction: column; }
        main { max-width: 1400px; margin: 0 auto; padding: 40px 20px; flex: 1; width: 100%; }
        footer { text-align: center; padding: 20px; color: white; opacity: 0.8; font-size: 12px; }
        
        .card { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card h2 { font-size: 22px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: #667eea; }
        
        .filter-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .filter-section input, .filter-section select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead { background: #f5f5f5; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-ativo { background: #d4edda; color: #155724; }
        .badge-pausado { background: #fff3cd; color: #856404; }
        .badge-concluido { background: #d1ecf1; color: #0c5460; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; margin-right: 5px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-secondary { background: #f0f0f0; color: #333; border: 1px solid #ddd; }
        .btn-danger { background: #ef4444; color: white; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        
        @media (max-width: 768px) {
            main { padding: 20px 10px; }
            .btn { display: block; width: 100%; margin: 5px 0; }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo"><h1>We Are Dvelopers</h1><p>Sistema de URA - Retell AI</p></div>
            <div class="header-actions">
                <span id="userName">Usuário</span>
                <button class="logout-btn" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Sair</button>
            </div>
        </div>
    </header>

    <div class="container">
        <main>
            <div class="card">
                <h2><i class="fas fa-handshake"></i> Campanhas de Vendas</h2>
                <div class="filter-section">
                    <div>
                        <label>Status</label>
                        <select id="statusFilter" onchange="applyFilters()">
                            <option value="">Todos</option>
                            <option value="ativo">Ativo</option>
                            <option value="pausado">Pausado</option>
                            <option value="concluido">Concluído</option>
                        </select>
                    </div>
                    <div>
                        <label>Buscar</label>
                        <input type="text" id="searchFilter" placeholder="Nome da campanha..." onchange="applyFilters()">
                    </div>
                    <div style="display: flex; align-items: flex-end; gap: 10px;">
                        <button class="btn btn-primary" onclick="applyFilters()"><i class="fas fa-search"></i> Filtrar</button>
                        <button class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-undo"></i> Limpar</button>
                        <button class="btn btn-primary" onclick="criarCampanha()"><i class="fas fa-plus"></i> Nova</button>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table id="campanhasTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Status</th>
                                <th>Leads</th>
                                <th>Vendas</th>
                                <th>Taxa Conversão</th>
                                <th>Data de Início</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="campanhasBody">
                            <tr><td colspan="7" class="empty-state">Carregando campanhas...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <div>Página <span id="pageNum">1</span></div>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="previousPage()">Anterior</button>
                        <button class="btn btn-secondary" onclick="nextPage()">Próxima</button>
                    </div>
                </div>
            </div>
        </main>

        <!-- <footer><p>&copy; 2026 We Are Dvelopers. Todos os direitos reservados.</p></footer> -->
    </div>


    <script src="/js/config.js"></script>
    <script src="/js/auth-helper.js"></script>
    <script>
        let currentPage = 1;

        document.addEventListener('DOMContentLoaded', function() {
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            if (user) document.getElementById('userName').textContent = user.name;
            loadCampanhas();
        });

        async function loadCampanhas() {
            try {
                // Usar fetchWithAuth para garantir autenticação
                const response = await fetchWithAuth(`${API_BASE_URL}/campanhas/vendas?page=${currentPage}`);
                const data = await response.json();
                renderTable(data);
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function renderTable(data) {
            const tbody = document.getElementById('campanhasBody');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Nenhuma campanha encontrada</td></tr>';
                return;
            }

            tbody.innerHTML = data.data.map(c => `
                <tr>
                    <td>${c.nome}</td>
                    <td><span class="badge badge-${c.status}">${c.status}</span></td>
                    <td>${c.total_leads}</td>
                    <td>${c.total_vendas}</td>
                    <td>${(c.conversao_rate || 0).toFixed(1)}%</td>
                    <td>${new Date(c.data_inicio).toLocaleDateString('pt-BR')}</td>
                    <td>
                        <button class="btn btn-primary" onclick="editarCampanha(${c.id})">Editar</button>
                        <button class="btn btn-secondary" onclick="verDetalhes(${c.id})">Ver</button>
                        <button class="btn btn-danger" onclick="arquivarCampanha(${c.id})">Arquivar</button>
                    </td>
                </tr>
            `).join('');

            document.getElementById('pageNum').textContent = currentPage;
        }

        function applyFilters() {
            currentPage = 1;
            loadCampanhas();
        }

        function clearFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('searchFilter').value = '';
            applyFilters();
        }

        function previousPage() {
            if (currentPage > 1) { currentPage--; loadCampanhas(); }
        }

        function nextPage() {
            currentPage++; loadCampanhas();
        }

        function criarCampanha() {
            window.location.href = '/campanhas/criar';
        }

        function editarCampanha(id) {
            window.location.href = `/campanhas/${id}/editar`;
        }

        function verDetalhes(id) {
            window.location.href = `/campanhas/${id}`;
        }

        function arquivarCampanha(id) {
            if (confirm('Deseja arquivar esta campanha?')) {
                console.log('Arquivar campanha:', id);
            }
        }

        async function logout() {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
    </script>
</body>
</html>

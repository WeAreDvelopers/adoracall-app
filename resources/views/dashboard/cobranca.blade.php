<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Cobrança - AdoraCall</title>
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
        
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .metric-card { padding: 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 8px; text-align: center; }
        .metric-card .label { font-size: 12px; opacity: 0.9; }
        .metric-card .value { font-size: 32px; font-weight: bold; margin: 10px 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead { background: #f5f5f5; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        
        @media (max-width: 768px) {
            main { padding: 20px 10px; }
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
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
                <h2><i class="fas fa-file-invoice-dollar"></i> Dashboard de Cobrança</h2>
                <div class="metrics-grid">
                    <div class="metric-card"><div class="label">Total Coletado</div><div class="value" id="totalColetado">R$ 0</div></div>
                    <div class="metric-card"><div class="label">Taxa de Conversão</div><div class="value" id="conversionRate">0%</div></div>
                    <div class="metric-card"><div class="label">Campanhas Ativas</div><div class="value" id="activeCampaigns">0</div></div>
                    <div class="metric-card"><div class="label">Contatos Processados</div><div class="value" id="processedContacts">0</div></div>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-chart-bar"></i> Receita por Campanha</h2>
                <div style="overflow-x: auto;">
                    <table id="revenueTable">
                        <thead>
                            <tr>
                                <th>Campanha</th>
                                <th>Contatos</th>
                                <th>Conversão</th>
                                <th>Valor (R$)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="revenueBody">
                            <tr><td colspan="5" class="empty-state">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- <footer><p>&copy; 2026 We Are Dvelopers. Todos os direitos reservados.</p></footer> -->
    </div>


    <script src="/js/config.js"></script>
    <script src="/js/auth-helper.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            if (user) document.getElementById('userName').textContent = user.name;
            loadCobrancaDashboard();
        });

        async function loadCobrancaDashboard() {
            try {
                // Usar fetchWithAuth para garantir autenticação
                const response = await fetchWithAuth(`${API_BASE_URL}/dashboard/cobranca`);
                const data = await response.json();
                updateMetrics(data);
                renderRevenue(data);
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function updateMetrics(data) {
            const stats = data.stats || {};
            document.getElementById('totalColetado').textContent = 'R$ ' + (stats.total_coletado || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            document.getElementById('conversionRate').textContent = (stats.conversion_rate || 0).toFixed(1) + '%';
            document.getElementById('activeCampaigns').textContent = stats.active_campaigns || 0;
            document.getElementById('processedContacts').textContent = stats.processed_contacts || 0;
        }

        function renderRevenue(data) {
            const tbody = document.getElementById('revenueBody');
            if (!data.campanhas || data.campanhas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="empty-state">Nenhuma campanha encontrada</td></tr>';
                return;
            }

            tbody.innerHTML = data.campanhas.map(c => `
                <tr>
                    <td>${c.nome}</td>
                    <td>${c.total_contatos}</td>
                    <td>${(c.conversao_rate || 0).toFixed(1)}%</td>
                    <td>R$ ${(c.valor_coletado || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                    <td>${c.status}</td>
                </tr>
            `).join('');
        }

        async function logout() {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
    </script>
</body>
</html>

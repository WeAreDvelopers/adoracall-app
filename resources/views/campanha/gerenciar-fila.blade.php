@extends('layouts.app')

@section('title', 'Controle de Fila - URA Dvelopers')

@section('content')
<div class="page-header">
                <h1>📞 Controle de Fila</h1>
                <p>Gerencie a fila de ligações do Agente AI</p>
            </div>

            <!-- Status Section -->
            <div class="notion-card">
                <div class="status-header">
                    <h2>Status da Fila</h2>
                    <span class="status-badge" id="statusBadge">
                        <i class="fas fa-circle loading"></i> Carregando...
                    </span>
                </div>

                <!-- Metrics -->
                <div class="notion-metrics-grid">
                    <div class="metric-card">
                        <h3>Total na Fila</h3>
                        <div class="value" id="metricTotal">0</div>
                    </div>
                    <div class="metric-card">
                        <h3>Em Processamento</h3>
                        <div class="value" id="metricProcessing">0</div>
                    </div>
                    <div class="metric-card">
                        <h3>Concluídos</h3>
                        <div class="value" id="metricCompleted">0</div>
                    </div>
                    <div class="metric-card">
                        <h3>Falhados</h3>
                        <div class="value" id="metricFailed">0</div>
                    </div>
                </div>
            </div>

            <!-- Controls Section -->
            <div class="notion-card">
                <div class="status-header">
                    <h2>Controles de Fila</h2>
                </div>

                <div class="controls-section">
                    <div class="controls-grid">
                        <button class="notion-btn notion-btn-primary" onclick="startQueue()">
                            <i class="fas fa-play"></i> Iniciar
                        </button>
                        <button class="notion-btn notion-btn-warning" onclick="pauseQueue()">
                            <i class="fas fa-pause"></i> Pausar
                        </button>
                        <button class="notion-btn notion-btn-info" onclick="resumeQueue()">
                            <i class="fas fa-play"></i> Retomar
                        </button>
                        <button class="notion-btn notion-btn-danger" onclick="showStopConfirm()">
                            <i class="fas fa-stop"></i> Parar
                        </button>
                        <button class="notion-btn notion-btn-secondary" onclick="refreshStatus()">
                            <i class="fas fa-sync"></i> Atualizar
                        </button>
                    </div>
                    <div class="refresh-indicator" id="refreshIndicator">
                        Auto-atualização: <strong id="autoRefreshStatus">Desativada</strong>
                    </div>
                </div>

                <!-- Auto-refresh toggle -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="autoRefreshToggle" onchange="toggleAutoRefresh()">
                        <span>Auto-atualizar a cada 5 segundos</span>
                    </label>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="activity-log" style="margin-top: 30px;">
                <h3>📋 Registro de Atividades</h3>
                <div class="log-entries" id="logEntries">
                    <div class="log-entry info">
                        <span class="log-entry-time">00:00</span>
                        <span class="log-entry-icon"><i class="fas fa-info-circle"></i></span>
                        <span class="log-entry-message">Aguardando ações...</span>
                    </div>
                </div>
            </div>
<!-- <footer>
            <p>&copy; 2026 We Are Dvelopers. Todos os direitos reservados.</p>
        </footer> -->
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h2 id="modalTitle">Confirmar Ação</h2>
            <p id="modalMessage">Tem certeza?</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-confirm" onclick="confirmAction()">
                    Confirmar
                </button>
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">
                    Cancelar
                </button>
            </div>
        </div>
    </div>


    
    
    
    
    
    <script>
        let autoRefreshInterval = null;
        let queueStatus = 'stopped';
        let pendingAction = null;

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            if (user) {
                document.getElementById('userName').textContent = '👤 ' + user.name;
            }

            refreshStatus();
        });

        // Funções de Status
        async function refreshStatus() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/status`);
                if (!response.ok) throw new Error('Erro ao buscar status');

                const data = await response.json();
                updateMetrics(data);
                updateStatusBadge(data.status);
                addLog('Status atualizado', 'success');
            } catch (error) {
                console.error('Erro ao atualizar status:', error);
                addLog('Erro ao atualizar status: ' + error.message, 'error');
                feedback.error('Erro ao atualizar status');
            }
        }

        function updateMetrics(data) {
            document.getElementById('metricTotal').textContent = data.total || 0;
            document.getElementById('metricProcessing').textContent = data.processing || 0;
            document.getElementById('metricCompleted').textContent = data.completed || 0;
            document.getElementById('metricFailed').textContent = data.failed || 0;
            queueStatus = data.status || 'stopped';
        }

        function updateStatusBadge(status) {
            const badge = document.getElementById('statusBadge');
            const statusTexts = {
                'running': '🟢 Rodando',
                'paused': '🟡 Pausada',
                'stopped': '🔴 Parada'
            };

            badge.textContent = statusTexts[status] || 'Desconhecido';
            badge.className = `status-badge ${status}`;
        }

        // Ações da Fila
        async function startQueue() {
            if (queueStatus === 'running') {
                feedback.warning('A fila já está rodando');
                return;
            }

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/start`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao iniciar fila');

                feedback.success('Fila iniciada com sucesso!');
                addLog('Fila iniciada', 'success');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao iniciar fila');
                addLog('Erro ao iniciar fila: ' + error.message, 'error');
            }
        }

        async function pauseQueue() {
            if (queueStatus !== 'running') {
                feedback.warning('A fila não está rodando');
                return;
            }

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/pause`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao pausar fila');

                feedback.success('Fila pausada!');
                addLog('Fila pausada', 'warning');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao pausar fila');
                addLog('Erro ao pausar fila: ' + error.message, 'error');
            }
        }

        async function resumeQueue() {
            if (queueStatus !== 'paused') {
                feedback.warning('A fila não está pausada');
                return;
            }

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/resume`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao retomar fila');

                feedback.success('Fila retomada!');
                addLog('Fila retomada', 'success');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao retomar fila');
                addLog('Erro ao retomar fila: ' + error.message, 'error');
            }
        }

        function showStopConfirm() {
            if (queueStatus === 'stopped') {
                feedback.warning('A fila já está parada');
                return;
            }

            document.getElementById('modalTitle').textContent = 'Parar Fila?';
            document.getElementById('modalMessage').textContent =
                'Isso irá parar o processamento de todas as ligações em andamento. Deseja continuar?';
            pendingAction = 'stop';
            document.getElementById('confirmModal').classList.add('active');
        }

        async function stopQueue() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/stop`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao parar fila');

                feedback.success('Fila parada!');
                addLog('Fila parada', 'error');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao parar fila');
                addLog('Erro ao parar fila: ' + error.message, 'error');
            }
        }

        // Modal
        function confirmAction() {
            closeModal();
            if (pendingAction === 'stop') {
                stopQueue();
            }
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingAction = null;
        }

        // Auto-refresh
        function toggleAutoRefresh() {
            const isChecked = document.getElementById('autoRefreshToggle').checked;

            if (isChecked) {
                autoRefreshInterval = setInterval(refreshStatus, 5000);
                document.getElementById('autoRefreshStatus').textContent = 'Ativada (5s)';
                document.getElementById('refreshIndicator').classList.add('active');
                addLog('Auto-atualização ativada', 'info');
            } else {
                clearInterval(autoRefreshInterval);
                document.getElementById('autoRefreshStatus').textContent = 'Desativada';
                document.getElementById('refreshIndicator').classList.remove('active');
                addLog('Auto-atualização desativada', 'info');
            }
        }

        // Activity Log
        function addLog(message, type = 'info') {
            const logEntries = document.getElementById('logEntries');
            const now = new Date();
            const time = now.toLocaleTimeString('pt-BR');

            const icons = {
                'success': 'check-circle',
                'error': 'exclamation-circle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };

            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `
                <span class="log-entry-time">${time}</span>
                <span class="log-entry-icon"><i class="fas fa-${icons[type]}"></i></span>
                <span class="log-entry-message">${message}</span>
            `;

            logEntries.insertBefore(entry, logEntries.firstChild);

            // Manter apenas os últimos 50 entries
            while (logEntries.children.length > 50) {
                logEntries.removeChild(logEntries.lastChild);
            }
        }

        // Logout
        async function logout() {
            const token = localStorage.getItem('api_token');

            try {
                await fetch(`${API_BASE_URL}/auth/logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
            }

            localStorage.removeItem('api_token');
            localStorage.removeItem('user');

            if (typeof feedback !== 'undefined') {
                feedback.success('Logout realizado com sucesso!');
            }

            setTimeout(() => {
                window.location.href = '/login';
            }, 1000);
        }
    </script>
@endsection

@push('scripts')
<script>

        let autoRefreshInterval = null;
        let queueStatus = 'stopped';
        let pendingAction = null;

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            if (user) {
                document.getElementById('userName').textContent = '👤 ' + user.name;
            }

            refreshStatus();
        });

        // Funções de Status
        async function refreshStatus() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/status`);
                if (!response.ok) throw new Error('Erro ao buscar status');

                const data = await response.json();
                updateMetrics(data);
                updateStatusBadge(data.status);
                addLog('Status atualizado', 'success');
            } catch (error) {
                console.error('Erro ao atualizar status:', error);
                addLog('Erro ao atualizar status: ' + error.message, 'error');
                feedback.error('Erro ao atualizar status');
            }
        }

        function updateMetrics(data) {
            document.getElementById('metricTotal').textContent = data.total || 0;
            document.getElementById('metricProcessing').textContent = data.processing || 0;
            document.getElementById('metricCompleted').textContent = data.completed || 0;
            document.getElementById('metricFailed').textContent = data.failed || 0;
            queueStatus = data.status || 'stopped';
        }

        function updateStatusBadge(status) {
            const badge = document.getElementById('statusBadge');
            const statusTexts = {
                'running': '🟢 Rodando',
                'paused': '🟡 Pausada',
                'stopped': '🔴 Parada'
            };

            badge.textContent = statusTexts[status] || 'Desconhecido';
            badge.className = `status-badge ${status}`;
        }

        // Ações da Fila
        async function startQueue() {
            if (queueStatus === 'running') {
                feedback.warning('A fila já está rodando');
                return;
            }

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/start`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao iniciar fila');

                feedback.success('Fila iniciada com sucesso!');
                addLog('Fila iniciada', 'success');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao iniciar fila');
                addLog('Erro ao iniciar fila: ' + error.message, 'error');
            }
        }

        async function pauseQueue() {
            if (queueStatus !== 'running') {
                feedback.warning('A fila não está rodando');
                return;
            }

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/pause`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao pausar fila');

                feedback.success('Fila pausada!');
                addLog('Fila pausada', 'warning');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao pausar fila');
                addLog('Erro ao pausar fila: ' + error.message, 'error');
            }
        }

        async function resumeQueue() {
            if (queueStatus !== 'paused') {
                feedback.warning('A fila não está pausada');
                return;
            }

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/resume`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao retomar fila');

                feedback.success('Fila retomada!');
                addLog('Fila retomada', 'success');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao retomar fila');
                addLog('Erro ao retomar fila: ' + error.message, 'error');
            }
        }

        function showStopConfirm() {
            if (queueStatus === 'stopped') {
                feedback.warning('A fila já está parada');
                return;
            }

            document.getElementById('modalTitle').textContent = 'Parar Fila?';
            document.getElementById('modalMessage').textContent =
                'Isso irá parar o processamento de todas as ligações em andamento. Deseja continuar?';
            pendingAction = 'stop';
            document.getElementById('confirmModal').classList.add('active');
        }

        async function stopQueue() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/queue/stop`, {
                    method: 'POST'
                });

                if (!response.ok) throw new Error('Erro ao parar fila');

                feedback.success('Fila parada!');
                addLog('Fila parada', 'error');
                await refreshStatus();
            } catch (error) {
                console.error('Erro:', error);
                feedback.error(error.message || 'Erro ao parar fila');
                addLog('Erro ao parar fila: ' + error.message, 'error');
            }
        }

        // Modal
        function confirmAction() {
            closeModal();
            if (pendingAction === 'stop') {
                stopQueue();
            }
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingAction = null;
        }

        // Auto-refresh
        function toggleAutoRefresh() {
            const isChecked = document.getElementById('autoRefreshToggle').checked;

            if (isChecked) {
                autoRefreshInterval = setInterval(refreshStatus, 5000);
                document.getElementById('autoRefreshStatus').textContent = 'Ativada (5s)';
                document.getElementById('refreshIndicator').classList.add('active');
                addLog('Auto-atualização ativada', 'info');
            } else {
                clearInterval(autoRefreshInterval);
                document.getElementById('autoRefreshStatus').textContent = 'Desativada';
                document.getElementById('refreshIndicator').classList.remove('active');
                addLog('Auto-atualização desativada', 'info');
            }
        }

        // Activity Log
        function addLog(message, type = 'info') {
            const logEntries = document.getElementById('logEntries');
            const now = new Date();
            const time = now.toLocaleTimeString('pt-BR');

            const icons = {
                'success': 'check-circle',
                'error': 'exclamation-circle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };

            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `
                <span class="log-entry-time">${time}</span>
                <span class="log-entry-icon"><i class="fas fa-${icons[type]}"></i></span>
                <span class="log-entry-message">${message}</span>
            `;

            logEntries.insertBefore(entry, logEntries.firstChild);

            // Manter apenas os últimos 50 entries
            while (logEntries.children.length > 50) {
                logEntries.removeChild(logEntries.lastChild);
            }
        }

        // Logout
        async function logout() {
            const token = localStorage.getItem('api_token');

            try {
                await fetch(`${API_BASE_URL}/auth/logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
            }

            localStorage.removeItem('api_token');
            localStorage.removeItem('user');

            if (typeof feedback !== 'undefined') {
                feedback.success('Logout realizado com sucesso!');
            }

            setTimeout(() => {
                window.location.href = '/login';
            }, 1000);
        }
    
</script>
@endpush

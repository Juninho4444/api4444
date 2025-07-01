// Client dashboard functionality
class ClientDashboard {
    constructor() {
        this.currentFilter = 'todos';
        this.currentLimit = 10;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadLogs();
    }

    setupEventListeners() {
        // Copy token button
        const copyTokenBtn = document.getElementById('copy-token-btn');
        if (copyTokenBtn) {
            copyTokenBtn.addEventListener('click', () => {
                const tokenElement = document.getElementById('client-token');
                if (tokenElement) {
                    Utils.copyToClipboard(tokenElement.textContent, copyTokenBtn);
                }
            });
        }

        // Filter buttons
        document.querySelectorAll('[data-filter]').forEach(button => {
            button.addEventListener('click', (e) => {
                const filter = e.target.dataset.filter;
                this.setFilter(filter);
            });
        });

        // Load more button
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => this.loadMoreLogs());
        }

        // Form validation
        this.setupFormValidation();
    }

    setupFormValidation() {
        const ipForm = document.getElementById('ip-form');
        if (ipForm) {
            ipForm.addEventListener('submit', (e) => {
                const ipInput = ipForm.querySelector('input[name="associated_ip"]');
                if (ipInput && !this.validateIP(ipInput.value)) {
                    e.preventDefault();
                    Utils.showNotification('Por favor, insira um endereço IP válido', 'error');
                }
            });
        }
    }

    validateIP(ip) {
        const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        return ipRegex.test(ip);
    }

    setFilter(filter) {
        this.currentFilter = filter;
        this.currentLimit = 10;
        
        // Update active button
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-200', 'text-gray-700', 'dark:bg-gray-600', 'dark:text-gray-300');
        });
        
        const activeBtn = document.querySelector(`[data-filter="${filter}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-200', 'text-gray-700', 'dark:bg-gray-600', 'dark:text-gray-300');
            activeBtn.classList.add('bg-blue-600', 'text-white');
        }
        
        this.loadLogs();
    }

    async loadLogs() {
        const container = document.getElementById('logs-container');
        const loadMoreBtn = document.getElementById('load-more-btn');
        
        if (container) {
            if (this.currentLimit === 10) {
                container.innerHTML = '<div class="animate-pulse p-4">Carregando logs...</div>';
            }
        }
        
        if (loadMoreBtn) {
            loadMoreBtn.disabled = true;
        }

        try {
            const response = await fetch(`get_client_logs.php?status=${this.currentFilter}&limit=${this.currentLimit}`);
            const logs = await response.json();

            if (logs.erro) {
                throw new Error(logs.erro);
            }

            this.renderLogs(logs);
            
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
                loadMoreBtn.style.display = logs.length < this.currentLimit ? 'none' : 'block';
            }

        } catch (error) {
            console.error('Erro ao carregar logs:', error);
            if (container) {
                container.innerHTML = '<p class="text-red-500 p-4">Erro ao carregar logs. Tente novamente.</p>';
            }
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
            }
        }
    }

    renderLogs(logs) {
        const container = document.getElementById('logs-container');
        if (!container) return;

        if (this.currentLimit === 10 && logs.length === 0) {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 p-4">Nenhum registro encontrado para este filtro.</p>';
            return;
        }

        const html = logs.map(log => {
            const statusClass = log.status === 'Sucesso' ? 'log-success' : 'log-error';
            const statusIcon = log.status === 'Sucesso' ? '✓' : '✗';
            
            return `
                <div class="log-item ${statusClass} border-l-4 ${log.status === 'Sucesso' ? 'border-green-500' : 'border-red-500'}">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-lg">${statusIcon}</span>
                            <span class="font-semibold">${log.status}</span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">${Utils.formatDate(log.access_time)}</span>
                    </div>
                    <div class="text-xs space-y-1 ml-6">
                        <div><strong>IP:</strong> ${log.ip_address}</div>
                        <div><strong>API:</strong> ${log.api_called}</div>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    loadMoreLogs() {
        this.currentLimit += 10;
        this.loadLogs();
    }
}

// Initialize client dashboard
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('client-dashboard')) {
        window.clientDashboard = new ClientDashboard();
    }
});
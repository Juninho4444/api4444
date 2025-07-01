// Admin panel functionality
class AdminPanel {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadLogs();
    }

    setupEventListeners() {
        // Copy token buttons
        document.querySelectorAll('[data-copy-token]').forEach(button => {
            button.addEventListener('click', (e) => {
                const tokenId = e.target.dataset.copyToken;
                const tokenElement = document.getElementById(tokenId);
                if (tokenElement) {
                    Utils.copyToClipboard(tokenElement.textContent, e.target);
                }
            });
        });

        // Modal handlers
        const addKeyBtn = document.getElementById('add-key-btn');
        if (addKeyBtn) {
            addKeyBtn.addEventListener('click', () => Utils.openModal('add-key-modal'));
        }

        // Filter logs
        const filterBtn = document.getElementById('filter-logs-btn');
        if (filterBtn) {
            filterBtn.addEventListener('click', () => this.loadLogs());
        }

        // Setup form validation
        this.setupFormValidation();
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Este campo é obrigatório');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        return isValid;
    }

    showFieldError(field, message) {
        field.classList.add('border-red-500');
        
        let errorElement = field.parentNode.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('p');
            errorElement.className = 'field-error text-red-500 text-xs mt-1';
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    async loadLogs() {
        const filterIp = document.getElementById('filter-ip')?.value || '';
        const filterToken = document.getElementById('filter-token')?.value || '';
        
        const successContainer = document.getElementById('logs-success');
        const failureContainer = document.getElementById('logs-failure');
        
        if (successContainer) successContainer.innerHTML = '<div class="animate-pulse">Carregando...</div>';
        if (failureContainer) failureContainer.innerHTML = '<div class="animate-pulse">Carregando...</div>';

        try {
            const response = await fetch(`get_admin_logs.php?ip=${encodeURIComponent(filterIp)}&token=${encodeURIComponent(filterToken)}`);
            const data = await response.json();

            if (data.erro) {
                throw new Error(data.erro);
            }

            this.renderLogs(successContainer, data.sucesso || [], 'success');
            this.renderLogs(failureContainer, data.falha || [], 'error');

        } catch (error) {
            console.error('Erro ao carregar logs:', error);
            if (successContainer) successContainer.innerHTML = '<p class="text-red-500">Erro ao carregar logs</p>';
            if (failureContainer) failureContainer.innerHTML = '<p class="text-red-500">Erro ao carregar logs</p>';
        }
    }

    renderLogs(container, logs, type) {
        if (!container) return;

        if (logs.length === 0) {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400">Nenhum registro encontrado</p>';
            return;
        }

        const logClass = type === 'success' ? 'log-success' : 'log-error';
        
        container.innerHTML = logs.map(log => `
            <div class="log-item ${logClass}">
                <div class="flex justify-between items-start mb-1">
                    <span class="font-semibold">${log.status}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">${Utils.formatDate(log.access_time)}</span>
                </div>
                <div class="text-xs space-y-1">
                    ${log.owner_name ? `<div><strong>Cliente:</strong> ${log.owner_name}</div>` : ''}
                    ${log.token_attempted ? `<div><strong>Token:</strong> ${log.token_attempted.substring(0, 20)}...</div>` : ''}
                    <div><strong>IP:</strong> ${log.ip_address}</div>
                    <div><strong>API:</strong> ${log.api_called}</div>
                </div>
            </div>
        `).join('');
    }

    openEditLoginModal(keyId, currentUsername) {
        const modal = document.getElementById('edit-login-modal');
        const keyIdInput = document.getElementById('edit-key-id');
        const usernameInput = document.getElementById('edit-username');
        const currentUsernameSpan = document.getElementById('current-username');
        
        if (modal && keyIdInput && usernameInput && currentUsernameSpan) {
            keyIdInput.value = keyId;
            usernameInput.value = currentUsername;
            currentUsernameSpan.textContent = currentUsername;
            Utils.openModal('edit-login-modal');
        }
    }

    showNewKeyModal(keyData) {
        const modal = document.getElementById('new-key-modal');
        const container = document.getElementById('new-key-data');
        
        if (modal && container && keyData) {
            let html = `
                <div class="space-y-3">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome:</label>
                            <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border text-sm font-mono">${keyData.owner_name || ''}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token:</label>
                            <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border text-sm font-mono break-all">${keyData.token_key || ''}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">IP:</label>
                            <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border text-sm font-mono">${keyData.associated_ip || ''}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Validade:</label>
                            <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border text-sm font-mono">${keyData.expiration_date || ''}</div>
                        </div>
                    </div>
            `;
            
            if (keyData.client_username) {
                html += `
                    <div class="border-t pt-3 mt-3">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Dados de Login do Cliente:</h4>
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuário:</label>
                                <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border text-sm font-mono">${keyData.client_username}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha:</label>
                                <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border text-sm font-mono">${keyData.client_password || '(sem alteração)'}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            container.innerHTML = html;
            Utils.openModal('new-key-modal');
        }
    }

    copyNewKeyData() {
        const container = document.getElementById('new-key-data');
        if (!container) return;

        const data = container.querySelectorAll('.font-mono');
        if (data.length === 0) return;

        let text = "DADOS DE ACESSO À API\n";
        text += "--------------------------------\n";
        text += `Cliente: ${data[0].textContent}\n`;
        text += `Token: ${data[1].textContent}\n`;
        text += `IP Autorizado: ${data[2].textContent}\n`;
        text += `Validade: ${data[3].textContent}`;

        if (data.length > 4) {
            text += "\n\n--------------------------------\n";
            text += "ACESSO AO PORTAL DO CLIENTE\n";
            text += "--------------------------------\n";
            text += `Usuário: ${data[4].textContent}\n`;
            text += `Senha: ${data[5].textContent}`;
        }

        Utils.copyToClipboard(text);
    }
}

// Initialize admin panel
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('admin-panel')) {
        window.adminPanel = new AdminPanel();
    }
});
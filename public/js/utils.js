// Utility functions
class Utils {
    // Copy text to clipboard
    static async copyToClipboard(text, button = null) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
            
            if (button) {
                this.showButtonFeedback(button, 'Copiado!', 'success');
            }
            
            this.showNotification('Texto copiado para a área de transferência!', 'success');
        } catch (err) {
            console.error('Erro ao copiar texto:', err);
            this.showNotification('Erro ao copiar texto', 'error');
        }
    }

    // Show button feedback
    static showButtonFeedback(button, message, type = 'success') {
        const originalText = button.textContent;
        const originalClasses = button.className;
        
        button.textContent = message;
        button.disabled = true;
        
        if (type === 'success') {
            button.className = button.className.replace(/bg-\w+-\d+/, 'bg-green-600');
        }
        
        setTimeout(() => {
            button.textContent = originalText;
            button.className = originalClasses;
            button.disabled = false;
        }, 2000);
    }

    // Show notification
    static showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
        
        const typeClasses = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-white',
            info: 'bg-blue-500 text-white'
        };
        
        notification.className += ` ${typeClasses[type] || typeClasses.info}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 10);
        
        // Animate out and remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, duration);
    }

    // Format date
    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR');
    }

    // Debounce function
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Modal management
    static openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    }

    static closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    }

    // Setup modal close handlers
    static setupModalHandlers() {
        // Close modal when clicking backdrop
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    this.closeModal(modal.id);
                }
            }
        });

        // Close modal with escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal:not(.hidden)');
                if (openModal) {
                    this.closeModal(openModal.id);
                }
            }
        });
    }
}

// Initialize utilities on page load
document.addEventListener('DOMContentLoaded', () => {
    Utils.setupModalHandlers();
});
// public/js/visualize.js

class LogsVisualizer {
    constructor() {
        this.config = window.visualizeConfig || {};
        this.currentFilters = this.config.currentFilters || {};
        this.debounceTimer = null;
        this.isLoading = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupExportFunctionality();
        this.setupTableSorting();
        this.setupContextToggles();

        console.log('📊 LogsVisualizer initialized');
    }

    setupEventListeners() {
        // Search input avec debounce
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.debounceFilter(() => {
                    this.updateFilter('search', e.target.value);
                });
            });
        }

        // Filtres select
        const severityFilter = document.getElementById('severity-filter');
        if (severityFilter) {
            severityFilter.addEventListener('change', (e) => {
                this.updateFilter('severity', e.target.value);
            });
        }

        const sortBy = document.getElementById('sort-by');
        if (sortBy) {
            sortBy.addEventListener('change', (e) => {
                this.updateFilter('sort_by', e.target.value);
            });
        }

        const sortOrder = document.getElementById('sort-order');
        if (sortOrder) {
            sortOrder.addEventListener('change', (e) => {
                this.updateFilter('sort_order', e.target.value);
            });
        }

        // Filtres de date
        const dateFrom = document.getElementById('date-from');
        if (dateFrom) {
            dateFrom.addEventListener('change', (e) => {
                this.updateFilter('date_from', e.target.value);
            });
        }

        const dateTo = document.getElementById('date-to');
        if (dateTo) {
            dateTo.addEventListener('change', (e) => {
                this.updateFilter('date_to', e.target.value);
            });
        }

        // Clear filters
        const clearFilters = document.getElementById('clear-filters');
        if (clearFilters) {
            clearFilters.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }

        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K pour focus sur search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput?.focus();
            }
        });
    }

    setupExportFunctionality() {
        const exportFormat = document.getElementById('export-format');
        const downloadBtn = document.getElementById('download-btn');

        if (downloadBtn && exportFormat) {
            downloadBtn.addEventListener('click', () => {
                const format = exportFormat.value;
                if (!format) {
                    this.showNotification('Please select an export format', 'warning');
                    return;
                }

                this.exportLogs(format);
            });

            // Enable/disable download button
            exportFormat.addEventListener('change', () => {
                downloadBtn.disabled = !exportFormat.value;
            });
        }
    }

    setupTableSorting() {
        const sortHeaders = document.querySelectorAll('.sort-header');

        sortHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const sortField = header.dataset.sort;
                const currentSort = this.currentFilters.sort_by;
                const currentOrder = this.currentFilters.sort_order;

                let newOrder = 'DESC';
                if (currentSort === sortField && currentOrder === 'DESC') {
                    newOrder = 'ASC';
                }

                this.updateFilter('sort_by', sortField);
                this.updateFilter('sort_order', newOrder);
            });
        });
    }

    setupContextToggles() {
        const contextToggles = document.querySelectorAll('.context-toggle');

        contextToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                const entryId = toggle.dataset.entryId;
                const contextDiv = document.getElementById(`context-${entryId}`);

                if (contextDiv) {
                    const isVisible = contextDiv.style.display !== 'none';
                    contextDiv.style.display = isVisible ? 'none' : 'block';
                    toggle.textContent = isVisible ? 'Show Context' : 'Hide Context';
                }
            });
        });
    }

    debounceFilter(callback, delay = 300) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(callback, delay);
    }

    updateFilter(key, value) {
        if (this.isLoading) return;

        // Mettre à jour les filtres actuels
        this.currentFilters[key] = value;

        // Construire la nouvelle URL
        const url = new URL(window.location);

        // Nettoyer les anciens paramètres
        Object.keys(this.currentFilters).forEach(k => {
            url.searchParams.delete(k);
        });

        // Ajouter les nouveaux paramètres
        Object.entries(this.currentFilters).forEach(([k, v]) => {
            if (v && v !== '' && v !== 'all') {
                url.searchParams.set(k, v);
            }
        });

        // Recharger la page avec les nouveaux filtres
        this.showLoading(true);
        window.location.href = url.toString();
    }

    clearAllFilters() {
        // Reset tous les inputs
        const searchInput = document.getElementById('search-input');
        const severityFilter = document.getElementById('severity-filter');
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');
        const sortBy = document.getElementById('sort-by');
        const sortOrder = document.getElementById('sort-order');

        if (searchInput) searchInput.value = '';
        if (severityFilter) severityFilter.value = 'all';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';
        if (sortBy) sortBy.value = 'timestamp';
        if (sortOrder) sortOrder.value = 'DESC';

        // Rediriger vers la page sans paramètres
        const baseUrl = this.config.visualizeUrl;
        this.showLoading(true);
        window.location.href = baseUrl;
    }

    exportLogs(format) {
        this.showLoading(true);

        // Construire l'URL d'export avec les filtres actuels
        const exportUrl = this.config.exportUrl.replace('FORMAT', format);
        const url = new URL(exportUrl, window.location.origin);

        // Ajouter les filtres actuels
        Object.entries(this.currentFilters).forEach(([key, value]) => {
            if (value && value !== '' && value !== 'all') {
                url.searchParams.set(key, value);
            }
        });

        console.log('📤 Exporting logs:', format, url.toString());

        // Créer un lien de téléchargement invisible
        const downloadLink = document.createElement('a');
        downloadLink.href = url.toString();
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        this.showLoading(false);
        this.showNotification(`Export ${format.toUpperCase()} started`, 'success');
    }

    showLoading(show) {
        this.isLoading = show;
        const loadingIndicator = document.getElementById('loading-indicator');
        const tableContainer = document.querySelector('.logs-table-container');

        if (loadingIndicator) {
            loadingIndicator.style.display = show ? 'flex' : 'none';
        }

        if (tableContainer) {
            tableContainer.style.opacity = show ? '0.6' : '1';
        }
    }

    showNotification(message, type = 'info') {
        // Créer une notification toast
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        // Styles inline pour la notification
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            borderRadius: '8px',
            color: 'white',
            fontWeight: '500',
            zIndex: '9999',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease',
            maxWidth: '300px'
        });

        // Couleurs selon le type
        const colors = {
            success: '#10b981',
            warning: '#f59e0b',
            error: '#ef4444',
            info: '#3b82f6'
        };
        notification.style.background = colors[type] || colors.info;

        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Suppression automatique
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Utilitaires pour la recherche en temps réel (optionnel)
    highlightSearchTerms() {
        const searchTerm = this.currentFilters.search;
        if (!searchTerm) return;

        const messageElements = document.querySelectorAll('.message-content');
        messageElements.forEach(element => {
            const text = element.textContent;
            const highlightedText = text.replace(
                new RegExp(`(${searchTerm})`, 'gi'),
                '<mark>$1</mark>'
            );
            if (text !== highlightedText) {
                element.innerHTML = highlightedText;
            }
        });
    }

    // Statistiques en temps réel (optionnel)
    updateStatistics(logEntries) {
        const stats = {
            Critical: 0,
            Medium: 0,
            Low: 0,
            Bug: 0
        };

        logEntries.forEach(entry => {
            const severity = entry.dataset.severity;
            if (stats.hasOwnProperty(severity)) {
                stats[severity]++;
            }
        });

        // Mettre à jour les cards
        Object.entries(stats).forEach(([severity, count]) => {
            const card = document.querySelector(`.stat-card.${severity.toLowerCase()} .stat-number`);
            if (card) {
                card.textContent = count;
            }
        });
    }
}

// Classe pour gérer les raccourcis clavier
class KeyboardShortcuts {
    constructor(visualizer) {
        this.visualizer = visualizer;
        this.setupShortcuts();
    }

    setupShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ignorer si on tape dans un input
            if (e.target.matches('input, textarea, select')) {
                return;
            }

            switch(e.key.toLowerCase()) {
                case '/':
                    e.preventDefault();
                    document.getElementById('search-input')?.focus();
                    break;

                case 'c':
                    if (!e.ctrlKey && !e.metaKey) {
                        this.visualizer.updateFilter('severity', 'Critical');
                    }
                    break;

                case 'm':
                    if (!e.ctrlKey && !e.metaKey) {
                        this.visualizer.updateFilter('severity', 'Medium');
                    }
                    break;

                case 'l':
                    if (!e.ctrlKey && !e.metaKey) {
                        this.visualizer.updateFilter('severity', 'Low');
                    }
                    break;

                case 'a':
                    if (!e.ctrlKey && !e.metaKey) {
                        this.visualizer.updateFilter('severity', 'all');
                    }
                    break;

                case 'escape':
                    this.visualizer.clearAllFilters();
                    break;
            }
        });
    }
}

// Auto-refresh functionality (optionnel)
class AutoRefresh {
    constructor(visualizer) {
        this.visualizer = visualizer;
        this.intervalId = null;
        this.isEnabled = false;
    }

    start(intervalMs = 30000) { // 30 secondes par défaut
        if (this.intervalId) return;

        this.intervalId = setInterval(() => {
            if (!this.visualizer.isLoading) {
                this.visualizer.updateFilter('_refresh', Date.now());
            }
        }, intervalMs);

        this.isEnabled = true;
        console.log('🔄 Auto-refresh enabled');
    }

    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            this.isEnabled = false;
            console.log('⏹️ Auto-refresh disabled');
        }
    }

    toggle() {
        if (this.isEnabled) {
            this.stop();
        } else {
            this.start();
        }
    }
}

// Initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 DOM ready, initializing logs visualizer...');

    const visualizer = new LogsVisualizer();
    const shortcuts = new KeyboardShortcuts(visualizer);
    const autoRefresh = new AutoRefresh(visualizer);

    // Exposer globalement pour le debug
    window.logsVisualizer = visualizer;
    window.autoRefresh = autoRefresh;

    // Afficher les raccourcis clavier (optionnel)
    console.log(`
    ⌨️ Keyboard shortcuts:
    / - Focus search
    C - Filter Critical
    M - Filter Medium
    L - Filter Low
    A - Show All
    Esc - Clear filters
    Ctrl+K - Focus search
    `);

    // Highlight des termes de recherche si présents
    if (visualizer.currentFilters.search) {
        setTimeout(() => {
            visualizer.highlightSearchTerms();
        }, 100);
    }
});

// Service Worker pour le cache (optionnel)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('🔧 SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('❌ SW registration failed: ', registrationError);
            });
    });
}

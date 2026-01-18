/**
 * MOHAA Stats - JavaScript for SMF
 *
 * @package MohaaStats
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Namespace
    window.MohaaStats = window.MohaaStats || {};

    /**
     * Fetch data from the API proxy
     */
    MohaaStats.fetch = async function(endpoint, params = {}) {
        const url = new URL(smf_scripturl);
        url.searchParams.set('action', 'mohaaapi');
        url.searchParams.set('endpoint', endpoint);
        
        for (const [key, value] of Object.entries(params)) {
            url.searchParams.set(key, value);
        }

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('API request failed');
            return await response.json();
        } catch (error) {
            console.error('MohaaStats API error:', error);
            return null;
        }
    };

    /**
     * Format large numbers
     */
    MohaaStats.formatNumber = function(n) {
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toLocaleString();
    };

    /**
     * Format time ago
     */
    MohaaStats.timeAgo = function(timestamp) {
        const seconds = Math.floor((Date.now() - new Date(timestamp * 1000)) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        return Math.floor(seconds / 86400) + 'd ago';
    };

    /**
     * Initialize live match auto-refresh
     */
    MohaaStats.initLiveRefresh = function(containerId, interval = 15000) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const refresh = async () => {
            const data = await MohaaStats.fetch('live');
            if (data) {
                container.innerHTML = MohaaStats.renderLiveMatches(data);
            }
        };

        setInterval(refresh, interval);
    };

    /**
     * Render live matches HTML
     */
    MohaaStats.renderLiveMatches = function(matches) {
        if (!matches || matches.length === 0) {
            return '<p class="centertext">No live matches at the moment.</p>';
        }

        return matches.map(match => `
            <div class="mohaa-live-match">
                <div class="live-indicator"><span class="pulse"></span> LIVE</div>
                <div class="live-server">${this.escapeHtml(match.server_name)}</div>
                <div class="live-map">${this.escapeHtml(match.map_name)}</div>
                <div class="live-players">${match.player_count}/${match.max_players} players</div>
                ${match.team_match ? `
                    <div class="live-score">
                        <span class="team-allies">${match.allies_score}</span>
                        <span class="vs">vs</span>
                        <span class="team-axis">${match.axis_score}</span>
                    </div>
                ` : ''}
            </div>
        `).join('');
    };

    /**
     * Escape HTML entities
     */
    MohaaStats.escapeHtml = function(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    /**
     * Initialize performance chart
     */
    MohaaStats.initPerformanceChart = function(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return;

        const ctx = canvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: 'Kills',
                        data: data.kills || [],
                        borderColor: '#4ade80',
                        backgroundColor: 'rgba(74, 222, 128, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Deaths',
                        data: data.deaths || [],
                        borderColor: '#f87171',
                        backgroundColor: 'transparent',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    };

    /**
     * Initialize weapon chart
     */
    MohaaStats.initWeaponChart = function(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return;

        const ctx = canvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(w => w.name),
                datasets: [{
                    data: data.map(w => w.kills),
                    backgroundColor: [
                        '#4a5d23',
                        '#8b9a4b',
                        '#c4b896',
                        '#d4a574',
                        '#6b7280'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    };

    /**
     * Initialize heatmap
     */
    MohaaStats.initHeatmap = function(containerId, mapImage, points, type = 'kills') {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Clear existing
        container.innerHTML = '';

        // Set background image
        container.style.backgroundImage = `url(${mapImage})`;
        container.style.backgroundSize = 'cover';
        container.style.backgroundPosition = 'center';

        // Render points
        points.forEach(point => {
            const dot = document.createElement('div');
            dot.className = `heatmap-point ${type}`;
            dot.style.left = `${point.x * 100}%`;
            dot.style.top = `${point.y * 100}%`;
            dot.style.width = `${Math.max(6, point.count * 2)}px`;
            dot.style.height = `${Math.max(6, point.count * 2)}px`;
            dot.title = `${point.count} ${type}`;
            container.appendChild(dot);
        });
    };

    /**
     * Tab switching
     */
    MohaaStats.initTabs = function() {
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update buttons
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update content
                document.querySelectorAll('.mohaa-tab-content').forEach(c => c.style.display = 'none');
                const tabId = 'tab-' + this.dataset.tab;
                const content = document.getElementById(tabId);
                if (content) content.style.display = 'block';
            });
        });
    };

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tabs if present
        if (document.querySelector('.tab-button')) {
            MohaaStats.initTabs();
        }

        // Initialize live refresh if container exists
        if (document.getElementById('mohaa-live-matches')) {
            MohaaStats.initLiveRefresh('mohaa-live-matches');
        }
    });

})();

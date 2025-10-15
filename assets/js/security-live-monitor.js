/**
 * Security Monitor - Simplified Working Version
 * 
 * @author Security Framework Team
 * @version 2.0 - Simplified
 */

class SecurityMonitor {
    constructor() {
        this.isActive = false;
        this.init();
    }
    
    init() {
        // Only initialize if we're on a page that needs it
        if (this.shouldInitialize()) {
            this.isActive = true;
            this.createNotificationContainer();
            this.startMonitoring();
            console.log('🛡️ Security Monitor Active');
        }
    }
    
    shouldInitialize() {
        // Only run on admin pages
        return window.location.pathname.includes('/admin/') || 
               window.location.pathname.includes('/logs/') ||
               window.location.pathname.includes('/patients/');
    }
    
    createNotificationContainer() {
        // Remove existing container if it exists
        const existing = document.getElementById('security-notifications');
        if (existing) {
            existing.remove();
        }
        
        const container = document.createElement('div');
        container.id = 'security-notifications';
        container.className = 'fixed top-4 right-4 z-[9999] space-y-2 max-w-sm';
        document.body.appendChild(container);
    }
    
    startMonitoring() {
        // Check for alerts every 30 seconds
        this.checkSecurityAlerts();
        setInterval(() => {
            if (this.isActive) {
                this.checkSecurityAlerts();
            }
        }, 30000);
        
        // Update badge every 60 seconds
        this.updateSecurityBadge();
        setInterval(() => {
            if (this.isActive) {
                this.updateSecurityBadge();
            }
        }, 60000);
    }
    
    async checkSecurityAlerts() {
        try {
            // Try multiple possible paths
            const paths = [
                '/Care/api/security_monitor.php',
                '../api/security_monitor.php',
                'api/security_monitor.php'
            ];
            
            let response = null;
            for (const path of paths) {
                try {
                    response = await fetch(`${path}?action=check_alerts`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    if (response.ok) break;
                } catch (e) {
                    continue;
                }
            }
            
            if (!response || !response.ok) {
                console.warn('Security monitor API not available');
                return;
            }
            
            const data = await response.json();
            if (data.success && data.alerts && data.alerts.length > 0) {
                this.showAlerts(data.alerts);
            }
            
        } catch (error) {
            console.warn('Security monitor check failed:', error.message);
        }
    }
    
    async updateSecurityBadge() {
        try {
            // Try multiple possible paths
            const paths = [
                '/Care/api/security_monitor.php',
                '../api/security_monitor.php',
                'api/security_monitor.php'
            ];
            
            let response = null;
            for (const path of paths) {
                try {
                    response = await fetch(`${path}?action=get_stats`);
                    if (response.ok) break;
                } catch (e) {
                    continue;
                }
            }
            
            if (!response || !response.ok) {
                return;
            }
            
            const data = await response.json();
            if (data.success && data.stats) {
                const hasRecentAlerts = data.stats.today_critical > 0 || data.stats.today_high > 0;
                this.updateBadge(hasRecentAlerts);
            }
            
        } catch (error) {
            // Silently fail - don't spam console
        }
    }
    
    showAlerts(alerts) {
        const container = document.getElementById('security-notifications');
        if (!container) return;
        
        // Clear existing alerts
        container.innerHTML = '';
        
        alerts.slice(0, 3).forEach(alert => {
            const notification = document.createElement('div');
            notification.className = 'security-notification bg-red-500 text-white p-3 rounded-lg shadow-lg';
            notification.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium">${alert.event_type || 'Security Alert'}</p>
                        <p class="text-xs mt-1">${alert.description || 'Security event detected'}</p>
                        <p class="text-xs mt-1 opacity-75">${this.getTimeAgo(alert.timestamp)}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 10000);
        });
    }
    
    updateBadge(hasAlerts) {
        const badge = document.getElementById('securityAlertBadge');
        if (badge) {
            if (hasAlerts) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }
    
    getTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        return `${Math.floor(diff / 86400)}d ago`;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new SecurityMonitor();
    });
} else {
    new SecurityMonitor();
}
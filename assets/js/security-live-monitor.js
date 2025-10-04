/**
 * Security Live Monitor
 * 
 * Real-time security notifications and monitoring
 * 
 * @author Security Framework Team
 * @version 1.0
 */

class SecurityMonitor {
    constructor() {
        this.checkInterval = null;
        this.alertSound = null;
        this.notificationsEnabled = true;
        this.lastCheckTime = null;
        this.activeAlerts = new Map();
        
        this.init();
    }
    
    init() {
        // Create notification container
        this.createNotificationContainer();
        
        // Load audio for alerts
        this.loadAlertSound();
        
        // Start monitoring
        this.startMonitoring();
        
        // Setup badge updates
        this.updateSecurityBadge();
        
        console.log('🛡️ Security Monitor Active');
    }
    
    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'security-notifications';
        container.className = 'fixed top-4 right-4 z-[9999] space-y-2 max-w-sm';
        document.body.appendChild(container);
        
        // Add CSS styles
        const style = document.createElement('style');
        style.textContent = `
            .security-notification {
                background: linear-gradient(135deg, #e74c3c, #c0392b);
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                backdrop-filter: blur(10px);
                border-left: 4px solid #fff;
                transform: translateX(100%);
                transition: transform 0.3s ease-out;
                cursor: pointer;
            }
            
            .security-notification.show {
                transform: translateX(0);
            }
            
            .security-notification.critical {
                background: linear-gradient(135deg, #e74c3c, #c0392b);
                border-left-color: #f1c40f;
            }
            
            .security-notification.high {
                background: linear-gradient(135deg, #f39c12, #e67e22);
                border-left-color: #fff;
            }
            
            .security-notification.medium {
                background: linear-gradient(135deg, #3498db, #2980b9);
                border-left-color: #fff;
            }
            
            .notification-header {
                display: flex;
                justify-content: between;
                align-items: center;
                margin-bottom: 4px;
            }
            
            .notification-title {
                font-weight: bold;
                font-size: 14px;
            }
            
            .notification-time {
                font-size: 12px;
                opacity: 0.8;
            }
            
            .notification-description {
                font-size: 12px;
                opacity: 0.9;
                line-height: 1.3;
            }
            
            .notification-close {
                position: absolute;
                top: 8px;
                right: 8px;
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                cursor: pointer;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            #securityAlertBadge {
                animation: pulse 1.5s infinite;
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
        `;
        document.head.appendChild(style);
    }
    
    loadAlertSound() {
        // Create audio context for alert sound
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.audioContext = audioContext;
        } catch (e) {
            console.warn('Audio context not supported');
        }
    }
    
    playAlertSound() {
        if (!this.notificationsEnabled || !this.audioContext) return;
        
        try {
            // Create a short beep sound
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, this.audioContext.currentTime);
            oscillator.frequency.setValueAtTime(600, this.audioContext.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, this.audioContext.currentTime + 0.01);
            gainNode.gain.linearRampToValueAtTime(0, this.audioContext.currentTime + 0.3);
            
            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + 0.3);
        } catch (e) {
            console.warn('Could not play alert sound:', e);
        }
    }
    
    startMonitoring() {
        // Check every 30 seconds for security alerts
        this.checkInterval = setInterval(() => {
            this.checkSecurityAlerts();
        }, 30000);
        
        // Initial check
        this.checkSecurityAlerts();
    }
    
    async checkSecurityAlerts() {
        try {
            const response = await fetch('/Care/api/security_monitor.php?action=check_alerts', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                console.warn('Security monitor API not available');
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.alerts.length > 0) {
                this.handleNewAlerts(data.alerts);
            }
            
            // Also check the security badge status
            this.updateSecurityBadge();
            
        } catch (error) {
            console.warn('Security monitor check failed:', error);
        }
    }
    
    handleNewAlerts(alerts) {
        alerts.forEach(alert => {
            const alertId = alert.id;
            
            // Skip if we already processed this alert
            if (this.activeAlerts.has(alertId)) {
                return;
            }
            
            // Mark as processed
            this.activeAlerts.set(alertId, alert);
            
            // Show notification for new alerts
            this.showNotification(alert);
            
            // Play sound for critical/high alerts
            if (['critical', 'high'].includes(alert.severity.toLowerCase())) {
                this.playAlertSound();
            }
            
            // Show badge
            this.showSecurityBadge();
        });
        
        // Clean up old alerts (older than 5 minutes)
        const cutoffTime = Date.now() - (5 * 60 * 1000);
        for (const [alertId, alert] of this.activeAlerts.entries()) {
            const alertTime = new Date(alert.timestamp).getTime();
            if (alertTime < cutoffTime) {
                this.activeAlerts.delete(alertId);
            }
        }
    }
    
    showNotification(alert) {
        if (!this.notificationsEnabled) return;
        
        const notification = document.createElement('div');
        notification.className = `security-notification ${alert.severity.toLowerCase()}`;
        notification.innerHTML = `
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
            <div class="notification-header">
                <span class="notification-title">🚨 ${alert.event_type}</span>
                <span class="notification-time">${alert.time_ago}</span>
            </div>
            <div class="notification-description">
                ${alert.description}<br>
                <small>IP: ${alert.ip_address}</small>
            </div>
        `;
        
        // Add click handler to view details
        notification.onclick = () => {
            if (confirm('View security logs for details?')) {
                window.open('/Care/logs/logs.php?filter=security', '_blank');
            }
            notification.remove();
        };
        
        // Add to container
        const container = document.getElementById('security-notifications');
        container.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 8000);
    }
    
    showSecurityBadge() {
        const badge = document.getElementById('securityAlertBadge');
        if (badge) {
            badge.classList.remove('hidden');
        }
    }
    
    hideSecurityBadge() {
        const badge = document.getElementById('securityAlertBadge');
        if (badge) {
            badge.classList.add('hidden');
        }
    }
    
    async updateSecurityBadge() {
        try {
            const response = await fetch('/Care/api/security_monitor.php?action=get_stats');
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.stats) {
                    const hasRecentAlerts = data.stats.today_critical > 0 || data.stats.today_high > 0;
                    
                    if (hasRecentAlerts) {
                        this.showSecurityBadge();
                    } else {
                        this.hideSecurityBadge();
                    }
                }
            }
        } catch (error) {
            console.warn('Could not update security badge:', error);
        }
    }
    
    stop() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }
}

// Initialize security monitor when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in
    if (typeof CurrentUser !== 'undefined' && CurrentUser.isLoggedIn) {
        window.securityMonitor = new SecurityMonitor();
    }
});

// Initialize security monitor if already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof CurrentUser !== 'undefined' && CurrentUser.isLoggedIn) {
            window.securityMonitor = new SecurityMonitor();
        }
    });
} else {
    if (typeof CurrentUser !== 'undefined' && CurrentUser.isLoggedIn) {
        window.securityMonitor = new SecurityMonitor();
    }
}

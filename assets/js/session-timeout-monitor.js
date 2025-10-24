/**
 * Session Timeout Monitor
 * Warns users before session expires and handles automatic logout
 */

class SessionTimeoutMonitor {
    constructor(options = {}) {
        this.timeoutSeconds = options.timeoutSeconds || 600; // 10 minutes default
        this.warningTime = options.warningTime || 60; // Warn 1 minute before timeout
        this.checkInterval = options.checkInterval || 30000; // Check every 30 seconds
        this.warningShown = false;
        this.lastActivity = Date.now();
        this.sessionStartTime = Date.now();
        this.intervalId = null;
        
        this.init();
    }
    
    init() {
        // Start monitoring
        this.startMonitoring();
        
        // Track user activity to reset warning
        this.trackActivity();
        
        // Show warning modal if needed
        this.checkSessionStatus();
    }
    
    startMonitoring() {
        this.intervalId = setInterval(() => {
            this.checkSessionStatus();
        }, this.checkInterval);
    }
    
    trackActivity() {
        // Track various user activities
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivity = Date.now();
                this.warningShown = false; // Reset warning when user is active
            }, true);
        });
    }
    
    checkSessionStatus() {
        const now = Date.now();
        const timeSinceLastActivity = (now - this.lastActivity) / 1000;
        const timeUntilTimeout = this.timeoutSeconds - timeSinceLastActivity;
        
        // Show warning if we're within warning time and haven't shown it yet
        if (timeUntilTimeout <= this.warningTime && timeUntilTimeout > 0 && !this.warningShown) {
            this.showWarning(timeUntilTimeout);
        }
        
        // Auto logout if timeout exceeded
        if (timeUntilTimeout <= 0) {
            this.handleTimeout();
        }
    }
    
    showWarning(timeRemaining) {
        this.warningShown = true;
        const minutes = Math.ceil(timeRemaining / 60);
        
        // Create warning modal
        const modal = this.createWarningModal(minutes);
        document.body.appendChild(modal);
        
        // Show modal with animation
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'scale-95');
            modal.classList.add('opacity-100', 'scale-100');
        }, 100);
        
        // Start countdown
        this.startCountdown(modal, timeRemaining);
    }
    
    createWarningModal(minutes) {
        const modal = document.createElement('div');
        modal.id = 'sessionTimeoutModal';
        modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 opacity-0 scale-95 transition-all duration-300';
        
        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Session Timeout Warning</h3>
                        <p class="text-sm text-slate-600">Your session will expire soon</p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <p class="text-slate-700 mb-2">
                        You will be automatically logged out in:
                    </p>
                    <div class="text-center">
                        <span id="sessionCountdown" class="text-2xl font-bold text-yellow-600">
                            ${minutes}:00
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 text-center mt-2">
                        Click "Stay Logged In" to extend your session
                    </p>
                </div>
                
                <div class="flex gap-3">
                    <button id="extendSessionBtn" class="flex-1 px-4 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors">
                        Stay Logged In
                    </button>
                    <button id="logoutNowBtn" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        Logout Now
                    </button>
                </div>
            </div>
        `;
        
        return modal;
    }
    
    startCountdown(modal, timeRemaining) {
        const countdownElement = modal.querySelector('#sessionCountdown');
        const extendBtn = modal.querySelector('#extendSessionBtn');
        const logoutBtn = modal.querySelector('#logoutNowBtn');
        
        let timeLeft = timeRemaining;
        
        const countdownInterval = setInterval(() => {
            timeLeft--;
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                this.handleTimeout();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color as time runs out
            if (timeLeft <= 30) {
                countdownElement.className = 'text-2xl font-bold text-red-600';
            } else if (timeLeft <= 60) {
                countdownElement.className = 'text-2xl font-bold text-orange-600';
            }
        }, 1000);
        
        // Handle extend session button
        extendBtn.addEventListener('click', () => {
            clearInterval(countdownInterval);
            this.extendSession();
            this.closeModal(modal);
        });
        
        // Handle logout now button
        logoutBtn.addEventListener('click', () => {
            clearInterval(countdownInterval);
            this.logoutNow();
        });
    }
    
    extendSession() {
        // Reset activity time
        this.lastActivity = Date.now();
        this.warningShown = false;
        
        // Send request to extend session
        fetch('../api/extend_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: window.csrfToken || ''
            })
        }).catch(error => {
            console.warn('Failed to extend session:', error);
        });
        
        // Show success notification
        this.showNotification('Session extended successfully', 'success');
    }
    
    logoutNow() {
        // Redirect to logout
        window.location.href = '../auth/logout.php';
    }
    
    handleTimeout() {
        // Show timeout message
        this.showNotification('Session expired. Redirecting to login...', 'warning');
        
        // Redirect to login with timeout parameter
        setTimeout(() => {
            window.location.href = '../auth/login.php?timeout=1';
        }, 2000);
    }
    
    closeModal(modal) {
        modal.classList.remove('opacity-100', 'scale-100');
        modal.classList.add('opacity-0', 'scale-95');
        
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }
    
    showNotification(message, type = 'info') {
        // Use global notification system if available
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type, 5000);
        } else {
            // Fallback notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-[99999] px-4 py-2 rounded-lg text-white ${
                type === 'success' ? 'bg-green-500' : 
                type === 'warning' ? 'bg-yellow-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }
    }
    
    destroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
    }
}

// Initialize session monitor when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in and not on login page
    const isLoggedIn = window.CurrentUser && window.CurrentUser.isLoggedIn;
    const isLoginPage = window.location.pathname.includes('login.php') || 
                       window.location.pathname.includes('auth/login');
    
    if (isLoggedIn && !isLoginPage) {
        window.sessionMonitor = new SessionTimeoutMonitor({
            timeoutSeconds: 600, // 10 minutes
            warningTime: 60, // Warn 1 minute before
            checkInterval: 30000 // Check every 30 seconds
        });
    }
});

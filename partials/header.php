<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'CARE: Clinic Administration of Records System') ?></title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="geolocation=(), microphone=(), camera=()">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'clinic-blue': '#3971b8',
                        'clinic-tea': '#c8d69b',
                        'clinic-ivory': '#fbfcee',
                        'clinic-vanilla': '#f6e6a5',
                        'clinic-dark': '#343b1b',
                        'clinic-green': '#22c55e',
                        'clinic-purple': '#a855f7',
                        'clinic-red': '#e74c3c'
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                        'comfortaa': ['Comfortaa', 'cursive']
                    }
                }
            }
        }
        
        // Global user state for security monitor
        window.CurrentUser = {
            isLoggedIn: <?= isset($_SESSION['user']) && !empty($_SESSION['user']) ? 'true' : 'false' ?>,
            userId: <?= isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 'null' ?>,
            username: <?= isset($_SESSION['user']['username']) ? '"' . addslashes($_SESSION['user']['username']) . '"' : 'null' ?>
        };
        
        // Global CSRF token for session monitor
        window.csrfToken = '<?= csrf_token() ?>';
    </script>
    <!-- Security Live Monitor -->
    <script src="../assets/js/security-live-monitor.js"></script>
    <!-- Session Timeout Monitor -->
    <script src="../assets/js/session-timeout-monitor.js"></script>
    
    <!-- Logout Confirmation Script -->
    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout? You will need to log in again to access the system.');
        }
    </script>
    
    <!-- Notification System Script -->
    <script>
        class NotificationCenter {
            constructor() {
                this.bell = document.getElementById('notificationBell');
                this.dropdown = document.getElementById('notificationDropdown');
                this.badge = document.getElementById('notificationBadge');
                this.list = document.getElementById('notificationList');
                this.markAllReadBtn = document.getElementById('markAllRead');
                this.isOpen = false;
                this.refreshInterval = null;
                this.notifications = [];
                this.loading = false;
                
                this.init();
            }
            
            init() {
                if (!this.bell) return;
                
                // Toggle dropdown
                this.bell.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleDropdown();
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!this.dropdown.contains(e.target) && !this.bell.contains(e.target)) {
                        this.closeDropdown();
                    }
                });
                
                // Mark all as read
                if (this.markAllReadBtn) {
                    this.markAllReadBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.markAllAsRead();
                    });
                }
                
                // Load initial notifications
                this.loadNotifications();
                
                // Set up auto-refresh every 30 seconds
                this.refreshInterval = setInterval(() => {
                    if (this.isOpen) {
                        this.loadNotifications();
                    }
                }, 30000);
            }
            
            toggleDropdown() {
                if (this.isOpen) {
                    this.closeDropdown();
                } else {
                    this.openDropdown();
                }
            }
            
            openDropdown() {
                this.dropdown.classList.remove('hidden');
                this.isOpen = true;
                this.loadNotifications();
            }
            
            closeDropdown() {
                this.dropdown.classList.add('hidden');
                this.isOpen = false;
            }
            
            async loadNotifications() {
                // Prevent multiple simultaneous loads
                if (this.loading) {
                    console.log('Already loading notifications, skipping...');
                    return;
                }
                
                this.loading = true;
                
                try {
                    console.log('Loading notifications from server...');
                    // Show loading state
                    this.showLoading();
                    
                    const response = await fetch('../reports/get_notifications.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.notifications = data.notifications || [];
                        this.updateBadge(data.unread_count || 0);
                        this.renderNotifications(this.notifications);
                    } else {
                        console.error('Failed to load notifications:', data.error);
                        this.renderError(data.error || 'Failed to load notifications');
                    }
                } catch (error) {
                    console.error('Error loading notifications:', error);
                    this.renderError('Network error occurred');
                } finally {
                    this.loading = false;
                }
            }
            
            showLoading() {
                this.list.innerHTML = `
                    <div class="p-4 text-center text-clinic-dark/60">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-clinic-blue mx-auto mb-2"></div>
                        <p class="font-poppins text-sm">Loading notifications...</p>
                    </div>
                `;
            }
            
            updateBadge(count) {
                if (count > 0) {
                    this.badge.textContent = count > 99 ? '99+' : count;
                    this.badge.classList.remove('hidden');
                } else {
                    this.badge.classList.add('hidden');
                }
            }
            
            renderNotifications(notifications) {
                if (!notifications || notifications.length === 0) {
                    this.list.innerHTML = `
                        <div class="p-4 text-center text-clinic-dark/60">
                            <svg class="w-12 h-12 mx-auto mb-2 text-clinic-tea/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <p class="font-poppins">No notifications</p>
                        </div>
                    `;
                    return;
                }
                
                // Render all notifications (login_failed are now excluded from backend)
                this.list.innerHTML = notifications.map(notification => {
                    const timeAgo = this.getTimeAgo(notification.timestamp);
                    const iconClass = this.getIconClass(notification.type);
                    const bgClass = notification.read ? 'bg-white' : 'bg-clinic-ivory/50';
                    const unreadDot = !notification.read ? 
                        `<div class="flex-shrink-0 mt-1">
                            <div class="w-2 h-2 bg-clinic-red rounded-full"></div>
                        </div>` : '';
                    
                    // Create view details button instead of direct link
                    const viewDetailsBtn = notification.action_url ? 
                        `<button onclick="notificationCenter.showDetails('${notification.id}', '${this.escapeHtml(notification.title)}', '${this.escapeHtml(notification.message)}', '${notification.action_url}')" 
                                class="text-xs text-clinic-blue hover:text-clinic-blue/80 font-poppins underline cursor-pointer">
                            View Details →
                        </button>` : '';
                    
                    return `
                        <div class="notification-item ${bgClass} border-b border-clinic-tea/10 hover:bg-clinic-tea/10 transition-colors duration-200 cursor-pointer" 
                             data-id="${notification.id}" 
                             onclick="notificationCenter.markAsRead('${notification.id}')">
                            <div class="p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="w-8 h-8 rounded-full ${iconClass} flex items-center justify-center">
                                            ${this.getIcon(notification.type)}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <h4 class="text-sm font-poppins font-semibold text-clinic-dark truncate">${this.escapeHtml(notification.title)}</h4>
                                            <span class="text-xs text-clinic-dark/60 font-poppins">${timeAgo}</span>
                                        </div>
                                        <p class="text-sm text-clinic-dark/80 font-poppins mb-2 line-clamp-2">${this.escapeHtml(notification.message)}</p>
                                        ${viewDetailsBtn}
                                    </div>
                                    ${unreadDot}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
            
            renderError(errorMessage) {
                this.list.innerHTML = `
                    <div class="p-4 text-center text-clinic-red">
                        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="font-poppins text-sm">${errorMessage}</p>
                        <button onclick="notificationCenter.loadNotifications()" class="mt-2 px-3 py-1 text-xs bg-clinic-green text-white hover:bg-clinic-green/80 rounded-lg font-poppins transition-colors duration-200">Retry</button>
                    </div>
                `;
            }
            
            getIconClass(type) {
                const classes = {
                    'security': 'bg-clinic-red/20 text-clinic-red',
                    'system': 'bg-clinic-blue/20 text-clinic-blue',
                    'patient': 'bg-clinic-green/20 text-clinic-green',
                    'medical': 'bg-clinic-purple/20 text-clinic-purple',
                    'archive': 'bg-purple-100 text-purple-600',
                    'registration': 'bg-green-100 text-green-600',
                    'edit': 'bg-blue-100 text-blue-600',
                    'login': 'bg-green-100 text-green-600',
                    'logout': 'bg-red-100 text-red-600'
                };
                return classes[type] || 'bg-clinic-tea/20 text-clinic-tea';
            }
            
            getIcon(type) {
                const icons = {
                    'security': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                    'system': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>',
                    'patient': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
                    'medical': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
                    'archive': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V8z"></path></svg>',
                    'registration': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>',
                    'edit': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                    'login': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>',
                    'logout': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>'
                };
                return icons[type] || '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            }
            
            getTimeAgo(timestamp) {
                const now = new Date();
                const time = new Date(timestamp);
                const diffInSeconds = Math.floor((now - time) / 1000);
                
                if (diffInSeconds < 60) return 'Just now';
                if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
                if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
                if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
                return time.toLocaleDateString();
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            async markAsRead(notificationId) {
                try {
                    const response = await fetch('../reports/mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            notification_id: notificationId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Update the UI immediately
                        const item = this.list.querySelector(`[data-id="${notificationId}"]`);
                        if (item) {
                            item.classList.remove('bg-clinic-ivory/50');
                            item.classList.add('bg-white');
                            const dot = item.querySelector('.bg-clinic-red');
                            if (dot) dot.remove();
                        }
                        
                        // Update badge count
                        this.loadNotifications();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }
            
            async markAllAsRead() {
                try {
                    // Disable button during request
                    if (this.markAllReadBtn) {
                        this.markAllReadBtn.disabled = true;
                        this.markAllReadBtn.textContent = 'Marking...';
                    }
                    
                    const response = await fetch('../reports/mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=mark_all_read'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Update all notifications to read state
                        this.list.querySelectorAll('.notification-item').forEach(item => {
                            item.classList.remove('bg-clinic-ivory/50');
                            item.classList.add('bg-white');
                            const dot = item.querySelector('.bg-clinic-red');
                            if (dot) dot.remove();
                        });
                        
                        // Update badge to 0
                        this.updateBadge(0);
                        
                        // Show success message briefly
                        const originalText = this.markAllReadBtn.textContent;
                        this.markAllReadBtn.textContent = 'All Read!';
                        setTimeout(() => {
                            this.markAllReadBtn.textContent = originalText;
                        }, 1000);
                        
                        console.log('All notifications marked as read successfully');
                    } else {
                        console.error('Failed to mark all notifications as read:', data.error);
                        alert('Failed to mark all notifications as read. Please try again.');
                    }
                } catch (error) {
                    console.error('Error marking all notifications as read:', error);
                    alert('Network error occurred. Please try again.');
                } finally {
                    // Re-enable button
                    if (this.markAllReadBtn) {
                        this.markAllReadBtn.disabled = false;
                        this.markAllReadBtn.textContent = 'Mark all read';
                    }
                }
            }
            
            showDetails(notificationId, title, message, actionUrl) {
                // Create modal for notification details
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-[10000] flex items-center justify-center p-4';
                modal.innerHTML = `
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-comfortaa font-bold text-clinic-dark">${title}</h3>
                                <button onclick="this.closest('.fixed').remove()" class="p-1 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="mb-6">
                                <p class="text-clinic-dark/80 font-poppins text-sm leading-relaxed">${message}</p>
                            </div>
                            <div class="flex justify-center">
                                <button onclick="this.closest('.fixed').remove()" class="px-6 py-2 bg-clinic-blue hover:bg-clinic-blue/80 text-white rounded-lg font-poppins transition-colors duration-200">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Close modal when clicking outside
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
                
                // Mark notification as read when viewing details
                this.markAsRead(notificationId);
            }
            
            destroy() {
                if (this.refreshInterval) {
                    clearInterval(this.refreshInterval);
                }
            }
        }
        
        // Initialize notification center when DOM is loaded
        let notificationCenter;
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.notificationCenter) {
                notificationCenter = new NotificationCenter();
                window.notificationCenter = notificationCenter;
            }
        });
        
        // Clean up when page unloads
        window.addEventListener('beforeunload', () => {
            if (notificationCenter) {
                notificationCenter.destroy();
            }
        });
    </script>
    
    <!-- Global Notification System -->
    <script>
        // Global notification system that persists across page loads
        window.GlobalNotifications = {
            container: null,
            notifications: new Map(),
            
            init() {
                // Get or create the global notification container
                this.container = document.getElementById('globalNotificationContainer');
                if (!this.container) {
                    this.container = document.createElement('div');
                    this.container.id = 'globalNotificationContainer';
                    this.container.className = 'fixed top-4 right-4 z-[99999] space-y-2 pointer-events-none';
                    document.body.appendChild(this.container);
                }
                
                // Load persisted notifications from localStorage
                this.loadPersistedNotifications();
            },
            
            show(message, type = 'info', duration = 4000, persistent = false) {
                if (!this.container) this.init();
                
                // Filter out unauthorized_access messages completely
                if (message.includes('unauthorized_access') || message.includes('Unauthorized access attempt to:')) {
                    console.log('Unauthorized access notification filtered out');
                    return null;
                }
                
                // Create unique identifier for failed login attempts to prevent duplicates
                let id = 'notification-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                
                // Special handling for failed login attempts
                if (message.includes('login_failed') || message.includes('Invalid credentials') || message.includes('attempt')) {
                    const loginKey = 'login_failed_' + btoa(message).substr(0, 20);
                    
                    // Check if this exact login failure notification was already shown and closed
                    const closedNotifications = JSON.parse(localStorage.getItem('closedNotifications') || '[]');
                    if (closedNotifications.includes(loginKey)) {
                        console.log('Login failure notification already closed, skipping...');
                        return null;
                    }
                    
                    // Use the login key as ID for failed login attempts
                    id = loginKey;
                }
                
                // Create notification element
                const notification = document.createElement('div');
                notification.id = id;
                notification.className = `transform transition-all duration-500 ease-out translate-x-full opacity-0 max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-4 pointer-events-auto ${
                    type === 'success' ? 'border-l-4 border-l-green-500' : 
                    type === 'error' ? 'border-l-4 border-l-red-500' : 
                    type === 'warning' ? 'border-l-4 border-l-yellow-500' : 
                    'border-l-4 border-l-blue-500'
                }`;
                
                // Create content
                notification.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">
                                ${type === 'success' ? '✅' : 
                                  type === 'error' ? '❌' : 
                                  type === 'warning' ? '⚠️' : 
                                  'ℹ️'}
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">${message}</p>
                        </div>
                        <button onclick="window.GlobalNotifications.close('${id}')" class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                            <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                
                // Store notification data
                this.notifications.set(id, {
                    message,
                    type,
                    persistent,
                    timestamp: Date.now()
                });
                
                // Add to container
                this.container.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full', 'opacity-0');
                }, 100);
                
                // Auto remove if not persistent
                if (!persistent) {
                    setTimeout(() => {
                        this.close(id);
                    }, duration);
                }
                
                // Persist to localStorage if persistent
                if (persistent) {
                    this.persistNotifications();
                }
                
                return id;
            },
            
            close(id) {
                const notification = document.getElementById(id);
                if (notification) {
                    notification.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
                
                // Track closed notifications for failed login attempts
                if (id.startsWith('login_failed_')) {
                    const closedNotifications = JSON.parse(localStorage.getItem('closedNotifications') || '[]');
                    if (!closedNotifications.includes(id)) {
                        closedNotifications.push(id);
                        
                        // Keep only last 50 closed notifications to prevent localStorage bloat
                        const trimmed = closedNotifications.slice(-50);
                        localStorage.setItem('closedNotifications', JSON.stringify(trimmed));
                    }
                }
                
                // Remove from storage
                this.notifications.delete(id);
                this.persistNotifications();
            },
            
            closeAll() {
                this.notifications.forEach((_, id) => {
                    this.close(id);
                });
            },
            
            persistNotifications() {
                const persistentNotifications = Array.from(this.notifications.entries())
                    .filter(([_, data]) => data.persistent)
                    .map(([id, data]) => ({ id, ...data }));
                
                localStorage.setItem('globalNotifications', JSON.stringify(persistentNotifications));
            },
            
            loadPersistedNotifications() {
                try {
                    const stored = localStorage.getItem('globalNotifications');
                    if (stored) {
                        const notifications = JSON.parse(stored);
                        const now = Date.now();
                        
                        notifications.forEach(notificationData => {
                            // Filter out unauthorized_access messages
                            if (notificationData.message.includes('unauthorized_access') || 
                                notificationData.message.includes('Unauthorized access attempt to:')) {
                                console.log('Filtered out persisted unauthorized_access notification');
                                return;
                            }
                            
                            // Only show notifications that are less than 24 hours old
                            if (now - notificationData.timestamp < 24 * 60 * 60 * 1000) {
                                this.show(notificationData.message, notificationData.type, 0, true);
                            }
                        });
                        
                        // Clear old notifications
                        localStorage.removeItem('globalNotifications');
                    }
                } catch (error) {
                    console.error('Error loading persisted notifications:', error);
                    localStorage.removeItem('globalNotifications');
                }
                
                // Clean up old closed notifications (older than 1 hour)
                this.cleanupClosedNotifications();
            },
            
            cleanupClosedNotifications() {
                try {
                    const closedNotifications = JSON.parse(localStorage.getItem('closedNotifications') || '[]');
                    const now = Date.now();
                    const oneHourAgo = now - (60 * 60 * 1000);
                    
                    // Remove closed notifications older than 1 hour
                    const recentClosed = closedNotifications.filter(id => {
                        // Extract timestamp from ID if possible, or assume recent
                        return true; // For now, keep all closed notifications for 1 hour
                    });
                    
                    if (recentClosed.length !== closedNotifications.length) {
                        localStorage.setItem('closedNotifications', JSON.stringify(recentClosed));
                    }
                } catch (error) {
                    console.error('Error cleaning up closed notifications:', error);
                    localStorage.removeItem('closedNotifications');
                }
            },
            
            handleURLParameters() {
                const urlParams = new URLSearchParams(window.location.search);
                const message = urlParams.get('message');
                const messageType = urlParams.get('message_type') || urlParams.get('type') || 'info';
                
                if (message) {
                    // Decode the message
                    const decodedMessage = decodeURIComponent(message);
                    
                    // Filter out unauthorized_access messages
                    if (decodedMessage.includes('unauthorized_access') || decodedMessage.includes('Unauthorized access attempt to:')) {
                        console.log('Filtered out unauthorized_access notification from URL');
                        this.cleanURLParameters();
                        return;
                    }
                    
                    // Check if this is a login failure notification
                    if (decodedMessage.includes('login_failed') || decodedMessage.includes('Invalid credentials') || decodedMessage.includes('attempt')) {
                        // Use the global notification system which handles duplicates
                        this.show(decodedMessage, messageType, 8000);
                    } else {
                        // Regular notification
                        this.show(decodedMessage, messageType, 4000);
                    }
                    
                    // Clean up URL parameters
                    this.cleanURLParameters();
                }
            },
            
            cleanURLParameters() {
                const url = new URL(window.location);
                const paramsToRemove = ['message', 'message_type', 'type'];
                
                let hasChanges = false;
                paramsToRemove.forEach(param => {
                    if (url.searchParams.has(param)) {
                        url.searchParams.delete(param);
                        hasChanges = true;
                    }
                });
                
                if (hasChanges) {
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            }
        };
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            // Clean up any existing unauthorized_access notifications from localStorage
            try {
                const stored = localStorage.getItem('globalNotifications');
                if (stored) {
                    const notifications = JSON.parse(stored);
                    const filtered = notifications.filter(n => 
                        !n.message.includes('unauthorized_access') && 
                        !n.message.includes('Unauthorized access attempt to:')
                    );
                    
                    if (filtered.length !== notifications.length) {
                        console.log('Cleaned up unauthorized_access notifications from localStorage');
                        if (filtered.length > 0) {
                            localStorage.setItem('globalNotifications', JSON.stringify(filtered));
                        } else {
                            localStorage.removeItem('globalNotifications');
                        }
                    }
                }
            } catch (error) {
                console.error('Error cleaning localStorage:', error);
                localStorage.removeItem('globalNotifications');
            }
            
            window.GlobalNotifications.init();
            
            // Handle URL parameters for notifications across ALL pages
            window.GlobalNotifications.handleURLParameters();
        });
        
        // Global showNotification function that uses the global system
        window.showNotification = function(message, type = 'info', duration = 4000) {
            return window.GlobalNotifications.show(message, type, duration, false);
        };
        
        // Global showPersistentNotification function
        window.showPersistentNotification = function(message, type = 'info') {
            return window.GlobalNotifications.show(message, type, 0, true);
        };
        
        // Global function to clear all closed notifications (useful for testing)
        window.clearClosedNotifications = function() {
            localStorage.removeItem('closedNotifications');
            console.log('All closed notifications cleared');
        };
        
        // Debug function to check what's in localStorage
        window.debugNotifications = function() {
            const closed = JSON.parse(localStorage.getItem('closedNotifications') || '[]');
            console.log('Closed notifications:', closed);
            const global = JSON.parse(localStorage.getItem('globalNotifications') || '[]');
            console.log('Global notifications:', global);
        };
        
        // Override any existing showNotification functions on pages to use global system
        // This ensures ALL pages use the global notification system
        const originalShowNotification = window.showNotification;
        window.showNotification = function(message, type = 'info', duration = 4000) {
            // Always use the global system for consistency
            return window.GlobalNotifications.show(message, type, duration, false);
        };
        
        // Override any local notification containers to redirect to global system
        // This prevents individual pages from creating their own notification systems
        document.addEventListener('DOMContentLoaded', function() {
            // Find any local notification containers and hide them
            const localContainers = document.querySelectorAll('#notificationContainer:not(#globalNotificationContainer)');
            localContainers.forEach(container => {
                container.style.display = 'none';
                console.log('Hidden local notification container, using global system instead');
            });
            
            // Override any page-specific notification functions
            if (typeof window.showNotification === 'function' && window.showNotification !== window.GlobalNotifications.show) {
                console.log('Overriding page-specific showNotification function');
            }
        });
    </script>
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .font-comfortaa { font-family: 'Comfortaa', cursive; }
        
        /* Line clamp utility for notification text */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Notification dropdown animations */
        #notificationDropdown {
            transform: translateY(-10px);
            opacity: 0;
            transition: all 0.2s ease-out;
        }
        
        #notificationDropdown:not(.hidden) {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Notification badge pulse animation */
        #notificationBadge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Scrollbar styling for notification list */
        #notificationList::-webkit-scrollbar {
            width: 6px;
        }
        
        #notificationList::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        #notificationList::-webkit-scrollbar-thumb {
            background: #c8d69b;
            border-radius: 3px;
        }
        
        #notificationList::-webkit-scrollbar-thumb:hover {
            background: #3971b8;
        }
        
        /* Welcome message animation */
        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate(-50%, 20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla min-h-screen">
    <!-- Top Navigation Bar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-clinic-tea/20 shadow-lg">
        <div class="flex items-center justify-between px-4 py-3 h-20">
            <!-- Left side - Hamburger menu and logo -->
            <div class="flex items-center gap-4">
                <!-- Hamburger Menu Button - Only show when sidebar is enabled -->
                <?php if ($showSidebar ?? false): ?>
                <button id="sidebarToggle" class="p-2 rounded-xl bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue transition-all duration-200 hover:scale-105">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <?php endif; ?>
                
                <!-- Logo and Title -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-lg"></div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-comfortaa font-bold text-clinic-dark">CARE</h1>
                        <p class="text-xs text-clinic-dark/60 font-poppins">Clinic Administration of Records System</p>
                    </div>
                </div>
            </div>
            
            <!-- Right side - Navigation and logout -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell - only show if user is logged in -->
                <?php if (isset($_SESSION['user']) && !empty($_SESSION['user'])): ?>
                <div class="relative">
                    <button id="notificationBell" class="relative p-2 rounded-xl bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue transition-all duration-200 hover:scale-105">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="notificationBadge" class="absolute -top-1 -right-1 bg-clinic-red text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold hidden">0</span>
                    </button>
                    
                    <!-- Notification Dropdown -->
                    <div id="notificationDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl shadow-xl border border-clinic-tea/20 z-50 hidden">
                        <div class="p-4 border-b border-clinic-tea/20">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-comfortaa font-bold text-clinic-dark">Notifications</h3>
                                <button id="markAllRead" class="px-3 py-1 text-sm bg-clinic-blue text-white hover:bg-clinic-blue/80 rounded-lg font-poppins transition-colors duration-200">Mark all read</button>
                            </div>
                        </div>
                        <div id="notificationList" class="max-h-96 overflow-y-auto">
                            <!-- Notifications will be loaded here -->
                            <div class="p-4 text-center text-clinic-dark/60">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-clinic-blue mx-auto mb-2"></div>
                                Loading notifications...
                            </div>
                        </div>
                        <div class="p-3 border-t border-clinic-tea/20 bg-clinic-ivory/30">
                            <?php 
                            // Determine correct path to notifications.php based on current directory
                            $currentDir = basename(dirname($_SERVER['PHP_SELF']));
                            $notificationsPath = ($currentDir === 'admin' || $currentDir === 'patients' || $currentDir === 'logs' || $currentDir === 'reports' || $currentDir === 'medical' || $currentDir === 'rfid' || $currentDir === 'auth') ? '../notifications.php' : 'notifications.php';
                            ?>
                            <a href="<?= $notificationsPath ?>" class="block text-center text-sm text-clinic-blue hover:text-clinic-blue/80 font-poppins font-medium">View all notifications</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Index button - only show on login page -->
                <?php if (basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                <a href="../index.php" class="px-4 py-2 bg-clinic-ivory/60 hover:bg-clinic-ivory/80 text-clinic-dark rounded-xl font-poppins font-medium transition-all duration-200 hover:scale-105">
                    Home
                </a>
                <?php endif; ?>
                
                <!-- Logout - only show if user is logged in -->
                <?php if (isset($_SESSION['user']) && !empty($_SESSION['user'])): ?>
                <a href="../logout.php" class="px-4 py-2 bg-clinic-red/10 hover:bg-clinic-red/20 text-clinic-red rounded-xl font-poppins font-medium transition-all duration-200 hover:scale-105">
                    Logout
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Global Notification Container -->
    <div id="globalNotificationContainer" class="fixed top-4 right-4 z-[99999] space-y-2 pointer-events-none"></div>

    <!-- Sidebar -->
    <?php if ($showSidebar ?? false): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main id="mainContent" class="transition-all duration-300 ease-in-out pt-20 min-h-screen <?= ($showSidebar ?? false) ? 'lg:pl-80' : '' ?>">
        <div class="w-full h-full">

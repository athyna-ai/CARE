        </div>
    </main>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('appSidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (sidebarToggle && sidebar && mainContent) {
                // Initialize sidebar state
                sidebar.setAttribute('data-sidebar-state', 'hidden');
                
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isHidden = sidebar.getAttribute('data-sidebar-state') === 'hidden';
                    console.log('Sidebar toggle clicked. Current state:', isHidden ? 'hidden' : 'visible');
                    
                    if (isHidden) {
                        // Show sidebar
                        sidebar.setAttribute('data-sidebar-state', 'visible');
                        
                        // Adjust main content on desktop
                        if (window.innerWidth >= 1024) {
                            mainContent.classList.add('lg:pl-80');
                        }
                        console.log('Sidebar shown');
                    } else {
                        // Hide sidebar
                        sidebar.setAttribute('data-sidebar-state', 'hidden');
                        
                        // Adjust main content
                        mainContent.classList.remove('lg:pl-80');
                        console.log('Sidebar hidden');
                    }
                });
                
                // Handle window resize
                window.addEventListener('resize', function() {
                    if (window.innerWidth < 1024) {
                        // Mobile/tablet - always remove padding
                        mainContent.classList.remove('lg:pl-80');
                    } else {
                        // Desktop - add padding if sidebar is visible
                        if (sidebar.getAttribute('data-sidebar-state') === 'visible') {
                            mainContent.classList.add('lg:pl-80');
                        } else {
                            mainContent.classList.remove('lg:pl-80');
                        }
                    }
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth < 1024 && 
                        sidebar.getAttribute('data-sidebar-state') === 'visible' &&
                        !sidebar.contains(e.target) && 
                        !sidebarToggle.contains(e.target)) {
                        sidebar.setAttribute('data-sidebar-state', 'hidden');
                        mainContent.classList.remove('lg:pl-80');
                    }
                });
            } else {
                console.log('Sidebar elements not found:', {
                    sidebarToggle: !!sidebarToggle,
                    sidebar: !!sidebar,
                    mainContent: !!mainContent
                });
            }
        });
    </script>
    
    <script src="validation.js"></script>
</body>
</html>


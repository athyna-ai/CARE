        </div>
    </main>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, looking for sidebar elements...');
            
            // Try multiple times to find elements (in case of timing issues)
            let attempts = 0;
            const maxAttempts = 5;
            
            function findElements() {
                attempts++;
                console.log(`Attempt ${attempts} to find elements...`);
                
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebar = document.getElementById('appSidebar');
                const mainContent = document.getElementById('mainContent');
                
                console.log('Elements found:', {
                    sidebarToggle: sidebarToggle,
                    sidebar: sidebar,
                    mainContent: mainContent
                });
                
                if (sidebarToggle && sidebar && mainContent) {
                    setupSidebar(sidebarToggle, sidebar, mainContent);
                } else if (attempts < maxAttempts) {
                    console.log('Elements not found, retrying in 100ms...');
                    setTimeout(findElements, 100);
                } else {
                    console.log('Max attempts reached, elements not found');
                }
            }
            
            function setupSidebar(sidebarToggle, sidebar, mainContent) {
                console.log('Setting up sidebar functionality...');
                
                // Get saved sidebar state from localStorage, default to hidden
                const savedState = localStorage.getItem('sidebarState');
                const shouldBeVisible = savedState === 'visible';
                
                // Set initial state based on saved preference
                if (shouldBeVisible) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    sidebar.setAttribute('data-sidebar-state', 'visible');
                } else {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('translate-x-0');
                    sidebar.setAttribute('data-sidebar-state', 'hidden');
                }
                
                // Adjust main content padding if sidebar is visible on desktop
                if (shouldBeVisible && window.innerWidth >= 1024) {
                    mainContent.classList.add('lg:pl-80');
                }
                
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Sidebar toggle button clicked!');
                    
                    const isHidden = sidebar.classList.contains('-translate-x-full') || !sidebar.classList.contains('translate-x-0');
                    console.log('Sidebar toggle clicked. Current state:', isHidden ? 'hidden' : 'visible');
                    console.log('Sidebar classes before:', sidebar.className);
                    
                    if (isHidden) {
                        // Show sidebar
                        sidebar.classList.remove('-translate-x-full');
                        sidebar.classList.add('translate-x-0');
                        sidebar.setAttribute('data-sidebar-state', 'visible');
                        localStorage.setItem('sidebarState', 'visible');
                        
                        // Adjust main content on desktop
                        if (window.innerWidth >= 1024) {
                            mainContent.classList.add('lg:pl-80');
                        }
                        console.log('Sidebar shown');
                    } else {
                        // Hide sidebar
                        sidebar.classList.add('-translate-x-full');
                        sidebar.classList.remove('translate-x-0');
                        sidebar.setAttribute('data-sidebar-state', 'hidden');
                        localStorage.setItem('sidebarState', 'hidden');
                        
                        // Adjust main content
                        mainContent.classList.remove('lg:pl-80');
                        console.log('Sidebar hidden');
                    }
                    
                    console.log('Sidebar classes after:', sidebar.className);
                });
                
                // Handle window resize
                window.addEventListener('resize', function() {
                    if (window.innerWidth < 1024) {
                        // Mobile/tablet - always remove padding
                        mainContent.classList.remove('lg:pl-80');
                    } else {
                        // Desktop - add padding if sidebar is visible
                        const isVisible = sidebar.classList.contains('translate-x-0') && !sidebar.classList.contains('-translate-x-full');
                        if (isVisible) {
                            mainContent.classList.add('lg:pl-80');
                        } else {
                            mainContent.classList.remove('lg:pl-80');
                        }
                    }
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    const isVisible = sidebar.classList.contains('translate-x-0') && !sidebar.classList.contains('-translate-x-full');
                    if (window.innerWidth < 1024 && 
                        isVisible &&
                        !sidebar.contains(e.target) && 
                        !sidebarToggle.contains(e.target)) {
                        sidebar.classList.add('-translate-x-full');
                        sidebar.classList.remove('translate-x-0');
                        sidebar.setAttribute('data-sidebar-state', 'hidden');
                        localStorage.setItem('sidebarState', 'hidden');
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
            
            // Debug: Log initial state
            if (sidebar) {
                console.log('Sidebar initial classes:', sidebar.className);
                console.log('Sidebar translate-x-0:', sidebar.classList.contains('translate-x-0'));
                console.log('Sidebar -translate-x-full:', sidebar.classList.contains('-translate-x-full'));
            }
            
            // Fallback: Simple click handler for sidebar toggle
            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'sidebarToggle') {
                    console.log('Fallback click handler triggered');
                    const sidebar = document.getElementById('appSidebar');
                    const mainContent = document.getElementById('mainContent');
                    
                    if (sidebar && mainContent) {
                        if (sidebar.classList.contains('-translate-x-full')) {
                            // Show sidebar
                            sidebar.classList.remove('-translate-x-full');
                            sidebar.classList.add('translate-x-0');
                            sidebar.setAttribute('data-sidebar-state', 'visible');
                            localStorage.setItem('sidebarState', 'visible');
                            if (window.innerWidth >= 1024) {
                                mainContent.classList.add('lg:pl-80');
                            }
                            console.log('Fallback: Sidebar shown');
                        } else {
                            // Hide sidebar
                            sidebar.classList.add('-translate-x-full');
                            sidebar.classList.remove('translate-x-0');
                            sidebar.setAttribute('data-sidebar-state', 'hidden');
                            localStorage.setItem('sidebarState', 'hidden');
                            mainContent.classList.remove('lg:pl-80');
                            console.log('Fallback: Sidebar hidden');
                        }
                    }
                }
            });
            
            // Start looking for elements
            findElements();
        });
    </script>
    
    <script src="../core/validation.js"></script>
</body>
</html>


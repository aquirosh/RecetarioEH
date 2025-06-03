/**
 * Navigation Menu Controller
 * Handles the side menu functionality for the Recetario application
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get necessary DOM elements
    const openMenu = document.getElementById('openMenu');
    const closeMenu = document.getElementById('closeMenu');
    const sideMenu = document.getElementById('sideMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    
    if (!openMenu || !closeMenu || !sideMenu || !menuOverlay) {
        console.error('Some menu elements are missing from the page');
        return;
    }
    
    // Open menu function
    openMenu.addEventListener('click', function() {
        sideMenu.classList.add('active');
        menuOverlay.classList.add('active');
        document.body.classList.add('menu-open');
    });
    
    // Close menu functions
    closeMenu.addEventListener('click', closeMenuFunction);
    menuOverlay.addEventListener('click', closeMenuFunction);
    
    function closeMenuFunction() {
        sideMenu.classList.remove('active');
        menuOverlay.classList.remove('active');
        document.body.classList.remove('menu-open');
    }
    
    // Set active menu item based on current page
    setActiveMenuItem();
    
    // Handle logout confirmation
    const logoutLink = document.querySelector('a[href="logout.php"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                e.preventDefault();
            }
        });
    }
});

/**
 * Sets the "active" class on the current menu item based on URL
 */
function setActiveMenuItem() {
    // Get current page URL path
    const currentPath = window.location.pathname;
    
    // Get all menu items
    const menuItems = document.querySelectorAll('.side-menu ul li a');
    
    menuItems.forEach(item => {
        // Remove any existing active class
        item.classList.remove('active');
        
        // Get the item href
        const itemHref = item.getAttribute('href');
        
        // Special case for home page
        if (itemHref === 'index.php' && (currentPath === '/' || currentPath.endsWith('/index.php'))) {
            item.classList.add('active');
        }
        // For other pages
        else if (currentPath.includes(itemHref) && itemHref !== 'index.php') {
            item.classList.add('active');
        }
    });
}
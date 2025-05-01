/**
 * Recetario - Menú lateral simple y robusto
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("Simple menu script loaded");
    
    const menuButton = document.querySelector('.menu-button');
    const body = document.body;
    
    // Crear el contenedor del menú lateral
    const sideMenu = document.createElement('div');
    sideMenu.className = 'side-menu';
    
    // Obtener los elementos del menú actual
    const navLinks = document.querySelectorAll('nav ul li');
    
    // Crear el contenido del menú lateral
    const sideMenuContent = document.createElement('div');
    sideMenuContent.className = 'side-menu-content';
    
    // Crear encabezado del menú
    const menuHeader = document.createElement('div');
    menuHeader.className = 'menu-header';
    menuHeader.innerHTML = '<h3>Menú</h3><button class="close-menu">×</button>';
    sideMenuContent.appendChild(menuHeader);
    
    // Clonar los elementos del menú
    const menuList = document.createElement('ul');
    navLinks.forEach(function(item) {
        const newItem = item.cloneNode(true);
        menuList.appendChild(newItem);
    });
    
    sideMenuContent.appendChild(menuList);
    sideMenu.appendChild(sideMenuContent);
    
    // Agregar overlay
    const overlay = document.createElement('div');
    overlay.className = 'menu-overlay';
    
    // Agregar a la página
    body.appendChild(sideMenu);
    body.appendChild(overlay);
    
    // Función para abrir el menú
    function openMenu() {
        sideMenu.classList.add('active');
        overlay.classList.add('active');
        body.classList.add('menu-open');
    }
    
    // Función para cerrar el menú
    function closeMenu() {
        sideMenu.classList.remove('active');
        overlay.classList.remove('active');
        body.classList.remove('menu-open');
    }
    
    // Event listeners
    menuButton.addEventListener('click', function(e) {
        e.stopPropagation();
        openMenu();
        console.log("Menu opened");
    });
    
    document.querySelector('.close-menu').addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);
    
    // Cerrar menú al hacer clic en un enlace
    menuList.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', closeMenu);
    });
});
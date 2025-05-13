/**
 * Recetario - Funcionalidad de búsqueda
 * Este script maneja la funcionalidad de la búsqueda en el recetario
 */

(function() {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Search script loaded');
        
        // Inicializar componentes de búsqueda
        initSearchComponents();
        
        // Interceptar formulario de búsqueda para validación
        initSearchValidation();
        
        // Mejorar UX para mostrar/ocultar la búsqueda en móvil
        initMobileSearchToggle();
    });
    
    /**
     * Inicializa los componentes de búsqueda
     */
    function initSearchComponents() {
        // Auto-enfocar el campo de búsqueda en la página de búsqueda
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            // Dar tiempo al DOM para renderizar completamente
            setTimeout(() => {
                searchInput.focus();
                
                // Mover el cursor al final del texto si hay texto
                const inputValue = searchInput.value;
                searchInput.value = '';
                searchInput.value = inputValue;
            }, 100);
        }
        
        // Añadir evento para limpiar el campo de búsqueda
        const searchForms = document.querySelectorAll('.search-form, .nav-search-form');
        searchForms.forEach(form => {
            const input = form.querySelector('input[type="text"]');
            if (input && input.value) {
                addClearButton(input);
            } else if (input) {
                // Añadir el botón de limpiar cuando el usuario escriba algo
                input.addEventListener('input', function() {
                    if (this.value) {
                        addClearButton(this);
                    } else {
                        removeClearButton(this);
                    }
                });
            }
        });
    }
    
    /**
     * Añade un botón para limpiar el campo de búsqueda
     */
    function addClearButton(input) {
        // Verificar si ya existe un botón de limpiar
        let parent = input.parentElement;
        if (parent.querySelector('.search-clear-btn')) return;
        
        // Crear el botón
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'search-clear-btn';
        clearBtn.innerHTML = '&times;';
        clearBtn.addEventListener('click', function() {
            input.value = '';
            input.focus();
            removeClearButton(input);
        });
        
        // Añadir el botón después del input
        input.insertAdjacentElement('afterend', clearBtn);
        
        // Añadir clase al contenedor
        parent.classList.add('has-clear-btn');
    }
    
    /**
     * Elimina el botón para limpiar el campo de búsqueda
     */
    function removeClearButton(input) {
        const parent = input.parentElement;
        const clearBtn = parent.querySelector('.search-clear-btn');
        if (clearBtn) {
            clearBtn.remove();
            parent.classList.remove('has-clear-btn');
        }
    }
    
    /**
     * Inicializa validación en formularios de búsqueda
     */
    function initSearchValidation() {
        const searchForms = document.querySelectorAll('.search-form, .nav-search-form');
        
        searchForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const input = this.querySelector('input[type="text"]');
                if (!input || !input.value.trim()) {
                    e.preventDefault();
                    if (input) {
                        input.focus();
                        // Añadir efecto visual de error
                        input.classList.add('search-error');
                        setTimeout(() => {
                            input.classList.remove('search-error');
                        }, 1000);
                    }
                }
            });
        });
    }
    
    /**
     * Inicializa la funcionalidad para mostrar/ocultar búsqueda en móvil
     */
    function initMobileSearchToggle() {
        // Solo aplicar en dispositivos móviles
        if (window.innerWidth <= 768) {
            const navSearchContainer = document.querySelector('.nav-search-container');
            
            // Si no hay barra de búsqueda en la navegación, salir
            if (!navSearchContainer) return;
            
            // Crear botón para mostrar/ocultar búsqueda
            const searchToggleBtn = document.createElement('button');
            searchToggleBtn.className = 'search-toggle-btn';
            searchToggleBtn.setAttribute('aria-label', 'Mostrar búsqueda');
            searchToggleBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            `;
            
            // Añadir a la navegación
            const menuContainer = document.querySelector('.menu-container');
            if (menuContainer) {
                menuContainer.parentElement.insertBefore(searchToggleBtn, menuContainer.nextSibling);
            }
            
            // Manejar clic en el botón
            searchToggleBtn.addEventListener('click', function() {
                const isVisible = navSearchContainer.classList.contains('visible');
                
                if (isVisible) {
                    navSearchContainer.classList.remove('visible');
                    this.setAttribute('aria-label', 'Mostrar búsqueda');
                } else {
                    navSearchContainer.classList.add('visible');
                    this.setAttribute('aria-label', 'Ocultar búsqueda');
                    
                    // Enfocar el campo de búsqueda
                    const searchInput = navSearchContainer.querySelector('input');
                    if (searchInput) searchInput.focus();
                }
            });
            
            // Ocultar al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!navSearchContainer.contains(e.target) && 
                    e.target !== searchToggleBtn && 
                    !searchToggleBtn.contains(e.target) &&
                    navSearchContainer.classList.contains('visible')) {
                    navSearchContainer.classList.remove('visible');
                    searchToggleBtn.setAttribute('aria-label', 'Mostrar búsqueda');
                }
            });
        }
    }
    
    /**
     * Maneja el resaltado de términos de búsqueda
     * Se puede llamar después de cargar nuevo contenido por AJAX
     */
    function highlightSearchTerms(container, term) {
        if (!term || !container) return;
        
        term = term.trim();
        if (!term) return;
        
        // Texto plano (no dentro de elementos de formulario o scripts)
        highlightTextNodes(container, term);
    }
    
    /**
     * Busca y resalta términos en nodos de texto
     */
    function highlightTextNodes(element, term) {
        // Si el elemento es un nodo de texto
        if (element.nodeType === 3) {
            const text = element.nodeValue;
            const regex = new RegExp(escapeRegExp(term), 'gi');
            
            if (regex.test(text)) {
                const span = document.createElement('span');
                const highlightedText = text.replace(regex, match => 
                    `<span class="resaltado">${match}</span>`);
                span.innerHTML = highlightedText;
                
                // Reemplazar el nodo de texto con el span
                if (element.parentNode) {
                    element.parentNode.replaceChild(span, element);
                }
            }
        } else if (element.nodeType === 1) {
            // Si es un elemento, recorrer sus hijos
            // Ignorar ciertos elementos
            const tagName = element.tagName.toLowerCase();
            if (tagName === 'script' || tagName === 'style' || 
                tagName === 'textarea' || tagName === 'input') {
                return;
            }
            
            // Crear una copia de childNodes porque la colección puede cambiar
            const childNodes = Array.from(element.childNodes);
            childNodes.forEach(child => {
                highlightTextNodes(child, term);
            });
        }
    }
    
    /**
     * Escapa caracteres especiales para RegExp
     */
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Exponer funciones útiles
    window.recetarioSearch = {
        highlightSearchTerms
    };
})();
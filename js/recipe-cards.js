/**
 * Recetario - Funcionalidad de tarjetas de recetas
 * Este script maneja la navegación al hacer clic en las tarjetas de recetas
 * y la confirmación de eliminación de recetas
 */

// Función global para confirmar eliminación de recetas
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta receta? Esta acción no se puede deshacer.')) {
        window.location.href = 'backend/eliminar_receta.php?id=' + id;
    }
}

(function() {
    console.log("Recipe cards script loaded"); // Para depuración
    
    // Función para inicializar las tarjetas de recetas
    function initRecipeCards() {
        console.log("Initializing recipe cards"); // Para depuración
        
        // Manejar evento click en tarjetas de recetas
        const recipeCards = document.querySelectorAll('.recipe-card');
        
        if (!recipeCards.length) {
            console.log("No recipe cards found"); // Para depuración
            return;
        }
        
        recipeCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Evitar navegación si se hace clic en un enlace o botón
                if (e.target.tagName === 'A' || e.target.closest('a') || 
                    e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                    e.stopPropagation();
                    return;
                }
                
                // Obtener el enlace de esta tarjeta
                const linkElement = this.querySelector('.recipe-link-big');
                
                if (linkElement) {
                    const link = linkElement.getAttribute('href');
                    window.location.href = link;
                } else {
                    console.log("Recipe link not found in card"); // Para depuración
                }
            });
        });

        // Inicializar mensajes auto-cerrables si existen
        initAutoCloseMessages();
    }
    
    // Función para manejar mensajes que se cierran automáticamente
    function initAutoCloseMessages() {
        const messages = document.querySelectorAll('.message, .success-message, .error-message');
        
        if (messages.length) {
            messages.forEach(message => {
                // Agregar botón para cerrar manualmente
                if (!message.querySelector('.close-btn')) {
                    const closeBtn = document.createElement('button');
                    closeBtn.className = 'close-btn';
                    closeBtn.innerHTML = '&times;';
                    closeBtn.style.position = 'absolute';
                    closeBtn.style.right = '10px';
                    closeBtn.style.top = '10px';
                    closeBtn.style.background = 'none';
                    closeBtn.style.border = 'none';
                    closeBtn.style.fontSize = '20px';
                    closeBtn.style.cursor = 'pointer';
                    
                    // Asegurar que el mensaje tenga position relative
                    if (getComputedStyle(message).position === 'static') {
                        message.style.position = 'relative';
                    }
                    
                    message.appendChild(closeBtn);
                    
                    closeBtn.addEventListener('click', function() {
                        message.style.display = 'none';
                    });
                }
                
                // Auto-cerrar después de 5 segundos
                setTimeout(() => {
                    if (message.parentNode) {
                        message.style.opacity = '0';
                        message.style.transition = 'opacity 0.5s ease';
                        
                        setTimeout(() => {
                            if (message.parentNode) {
                                message.style.display = 'none';
                            }
                        }, 500);
                    }
                }, 5000);
            });
        }
    }
    
    // Si el DOM ya está cargado, inicializa las tarjetas de inmediato
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initRecipeCards();
    } else {
        // De lo contrario, espera a que el DOM esté listo
        document.addEventListener('DOMContentLoaded', initRecipeCards);
    }
})();
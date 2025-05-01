/**
 * Recetario - Funcionalidad de tarjetas de recetas
 * Este script maneja la navegación al hacer clic en las tarjetas de recetas
 */

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
                // Evitar navegación si se hace clic en un enlace
                if (e.target.tagName === 'A' || e.target.closest('a')) {
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
    }
    
    // Si el DOM ya está cargado, inicializa las tarjetas de inmediato
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initRecipeCards();
    } else {
        // De lo contrario, espera a que el DOM esté listo
        document.addEventListener('DOMContentLoaded', initRecipeCards);
    }
})();
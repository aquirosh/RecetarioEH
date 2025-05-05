// Selección de color (versión actualizada sin colorPreview)
const colorOptions = document.querySelectorAll('#colorOptions .color-option');
const colorInput = document.querySelector('#color');

colorOptions.forEach(option => {
    option.addEventListener('click', function() {
        // Obtener el color
        const color = this.getAttribute('data-color');
        
        // Actualizar solo el input hidden
        colorInput.value = color;
        
        // Quitar selección previa
        const prevSelected = document.querySelector('#colorOptions .selected');
        if (prevSelected) {
            prevSelected.classList.remove('selected');
        }
        
        // Añadir selección actual
        this.classList.add('selected');
    });
});
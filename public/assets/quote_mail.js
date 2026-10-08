'use strict';
document.querySelectorAll('[data-copy]').forEach(button => {
    button.addEventListener('click', async () => {
        const field = document.getElementById(button.dataset.copy);
        const status = document.getElementById('copy-status');
        try {
            await navigator.clipboard.writeText(field.value);
            status.textContent = 'Copiado. Pégalo en el campo correspondiente de webmail.';
        } catch (_) {
            field.focus();
            field.select();
            status.textContent = 'El navegador no permitió copiar. El texto está seleccionado: usa Ctrl+C o la opción Copiar del teléfono.';
        }
    });
});

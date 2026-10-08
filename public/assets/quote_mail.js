'use strict';
const mailForm = document.querySelector('form');
const outlookLink = document.getElementById('outlook-compose');
if (mailForm && outlookLink) {
    mailForm.addEventListener('submit', async event => {
        event.preventDefault();
        const status = document.getElementById('copy-status');
        const fallback = document.getElementById('mail-popup-fallback');
        // Open during the user's click, before the asynchronous validation.
        const popup = window.open('about:blank', '_blank', 'popup,width=1050,height=780,resizable=yes,scrollbars=yes');
        if (popup) popup.opener = null;
        const button = mailForm.querySelector('[type="submit"]');
        button.disabled = true;
        fallback.hidden = true;
        try {
            const data = new FormData(mailForm);
            data.set('response', 'json');
            const response = await fetch(mailForm.action, {method: 'POST', body: data, credentials: 'same-origin'});
            if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('La sesión pudo vencer. Vuelve a iniciar sesión en Metalrubber y abre esta ventana de nuevo.');
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'No se pudo preparar el enlace de correo.');
            const destination = new URL(result.url);
            if (destination.origin !== 'https://metalrubber.cl:2096') throw new Error('Destino de webmail inválido.');
            if (popup && !popup.closed) {
                popup.location.replace(destination.href);
                status.textContent = 'Webmail abierto en otra ventana. La cotización sigue aquí; recuerda adjuntar el PDF.';
            } else {
                fallback.href = destination.href;
                fallback.hidden = false;
                status.textContent = 'El navegador bloqueó la ventana. Usa Abrir webmail en otra pestaña, sin cerrar la cotización.';
            }
        } catch (error) {
            if (popup && !popup.closed) popup.close();
            status.textContent = error.message || 'No se pudo abrir webmail. Intenta nuevamente.';
        } finally {
            button.disabled = false;
        }
    });
    const updateOutlook = () => {
        const to = document.getElementById('to').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const body = document.getElementById('body').value.trim().replace(/\r?\n/g, '\r\n');
        outlookLink.href = 'mailto:' + encodeURIComponent(to) + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    };
    mailForm.addEventListener('input', updateOutlook);
    updateOutlook();
    outlookLink.addEventListener('click', event => {
        updateOutlook();
        const subject = document.getElementById('subject').value;
        if (!mailForm.reportValidity() || /[\r\n]/.test(subject)) {
            event.preventDefault();
            document.getElementById('copy-status').textContent = 'Revisa destinatario, asunto y mensaje antes de abrir Outlook.';
        } else if (outlookLink.href.length > 2000) {
            event.preventDefault();
            document.getElementById('copy-status').textContent = 'El mensaje es demasiado largo para un enlace. Abre Outlook y usa los botones Copiar.';
        } else {
            document.getElementById('copy-status').textContent = 'Se solicitó abrir tu aplicación de correo. Adjunta el PDF y verifica el remitente. No se ha enviado ningún mensaje.';
        }
    });
}
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

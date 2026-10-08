'use strict';
document.querySelectorAll('[data-eml-download]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        const status = form.parentElement.querySelector('[data-eml-status]');
        button.disabled = true;
        status.hidden = false;
        status.textContent = 'Preparando correo con PDF…';
        try {
            const data = new FormData(form);
            data.set('response', 'json');
            const response = await fetch(form.action, {method: 'POST', body: data, credentials: 'same-origin'});
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok || !contentType.includes('message/rfc822')) {
                if (contentType.includes('application/json')) {
                    const result = await response.json();
                    throw new Error(result.error || 'No se pudo descargar el correo.');
                }
                throw new Error('Recarga tu sesión e intenta nuevamente.');
            }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            const disposition = response.headers.get('content-disposition') || '';
            link.download = disposition.match(/filename="([A-Za-z0-9_.-]+)"/)?.[1] || 'cotizacion.eml';
            link.href = url;
            document.body.appendChild(link);
            link.click();link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 60000);
            status.textContent = 'Correo .eml descargado con PDF adjunto.';
        } catch (error) {
            status.textContent = error.message || 'No se pudo descargar el correo. Intenta nuevamente.';
        } finally {
            button.disabled = false;
        }
    });
});

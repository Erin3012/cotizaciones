'use strict';
const mailDialog = document.getElementById('quote-mail-dialog');
const mailFrame = document.getElementById('quote-mail-frame');
let mailTrigger;
if (mailDialog && mailFrame && typeof mailDialog.showModal === 'function') {
    document.querySelectorAll('[data-mail-dialog]').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            mailTrigger = link;
            // Keep the frame while closing so unsent edits survive reopening.
            if (mailFrame.getAttribute('src') !== link.getAttribute('href')) mailFrame.src = link.getAttribute('href');
            mailDialog.showModal();
        });
    });
    mailDialog.querySelector('[data-close-mail]').addEventListener('click', () => mailDialog.close());
    mailDialog.addEventListener('close', () => { if (mailTrigger) mailTrigger.focus(); });
}

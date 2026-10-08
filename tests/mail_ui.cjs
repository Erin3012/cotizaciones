const fs = require('fs');
const vm = require('vm');
const assert = require('assert/strict');
function field(value = '') {
    return {value, hidden: true, handlers: {}, addEventListener(type, fn) {this.handlers[type] = fn;}};
}
const fields = {
    to: field('prueba@example.invalid'), subject: field('Cotización — Metalrubber'), body: field('Hola\nCotización'),
    'copy-status': field(), 'mail-popup-fallback': field(), 'outlook-compose': field(),
};
let valid = true;
const submit = field();
const form = field();
form.action = 'https://cotizaciones.metalrubber.cl/quote_mail.php';
form.reportValidity = () => valid;
form.querySelector = () => submit;
let popup = null;
const document = {getElementById: id => fields[id], querySelector: () => form, querySelectorAll: () => []};
const context = {document, URL, navigator: {}, FormData: class {set() {}},
    window: {open: () => popup}, fetch: async () => ({ok: true, headers: {get: () => 'application/json'}, json: async () => ({url: 'https://metalrubber.cl:2096/3rdparty/roundcube/?_action=compose'})})};
vm.runInNewContext(fs.readFileSync('public/assets/quote_mail.js', 'utf8'), context);
assert(fields['outlook-compose'].href.startsWith('mailto:'));
assert(fields['outlook-compose'].href.includes(encodeURIComponent(fields.subject.value)));
let prevented = false;
valid = false;
fields['outlook-compose'].handlers.click({preventDefault() {prevented = true;}});
assert(prevented);
valid = true;
async function run() {
    await form.handlers.submit({preventDefault() {}});
    assert.equal(fields['mail-popup-fallback'].hidden, false, 'Popup bloqueado tiene alternativa');
    assert.equal(submit.disabled, false);
    let destination;
    popup = {closed: false, opener: {}, location: {replace(url) {destination = url;}}};
    await form.handlers.submit({preventDefault() {}});
    assert.equal(popup.opener, null, 'Webmail no controla la ventana original');
    assert(destination.startsWith('https://metalrubber.cl:2096/'));
    context.fetch = async () => ({ok: false, headers: {get: () => 'application/json'}, json: async () => ({error: 'Datos inválidos'})});
    popup.close = () => {popup.closed = true;};
    await form.handlers.submit({preventDefault() {}});
    assert.equal(popup.closed, true);
    assert.equal(fields['copy-status'].textContent, 'Datos inválidos');
    console.log('OK: Outlook, validación, popup, bloqueo y errores (sin abrir ventanas reales).');
}
run().catch(error => {console.error(error); process.exitCode = 1;});

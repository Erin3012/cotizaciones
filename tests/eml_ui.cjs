const fs = require('fs');
const vm = require('vm');
const assert = require('assert/strict');
const button = {};
const status = {hidden: true};
let submitHandler, downloaded = false, filename;
const form = {action: 'quote_mail.php', parentElement: {querySelector: () => status}, querySelector: () => button, addEventListener: (_, fn) => {submitHandler = fn;}};
const document = {querySelectorAll: () => [form], body: {appendChild() {}}, createElement: () => ({click() {downloaded = true;filename = this.download;},remove() {}})};
const context = {document, FormData: class {set() {}}, URL: {createObjectURL: () => 'blob:prueba',revokeObjectURL() {}}, setTimeout: fn => fn(),
    fetch: async () => ({ok: true,headers: {get: name => name === 'content-type' ? 'message/rfc822' : 'attachment; filename="COT-2026-0001.eml"'},blob: async () => ({})})};
vm.runInNewContext(fs.readFileSync('public/assets/quote_eml.js','utf8'),context);
(async () => {
    await submitHandler({preventDefault() {}});
    assert(downloaded);assert.equal(filename,'COT-2026-0001.eml');assert.equal(button.disabled,false);
    assert(status.textContent.includes('PDF adjunto'));
    context.fetch = async () => ({ok: false,headers: {get: () => 'application/json'},json: async () => ({error: 'Correo inválido'})});
    await submitHandler({preventDefault() {}});
    assert.equal(status.textContent,'Correo inválido');assert.equal(button.disabled,false);
    context.fetch = async () => {throw new Error('Sin conexión');};
    await submitHandler({preventDefault() {}});
    assert.equal(status.textContent,'Sin conexión');assert.equal(button.disabled,false);
    const source = fs.readFileSync('public/index.php','utf8');
    assert(!source.includes('quote-mail-dialog')&&!source.includes('quote_mail_modal'));
    assert(source.includes('data-eml-download'));
    console.log('OK: descarga EML directa, nombre, errores recuperables y sin ventanas intermedias.');
})().catch(e => {console.error(e);process.exitCode = 1;});

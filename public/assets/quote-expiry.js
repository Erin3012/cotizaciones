document.addEventListener('DOMContentLoaded', () => {
  const issue = document.querySelector('[name="issue_date"]');
  const expiry = document.querySelector('[name="expiry_date"]');
  if (!issue || !expiry) return;
  const displayDate = value => value ? value.split('-').reverse().join('/') : '';
  const localizedInput = original => {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = original.className;
    input.placeholder = 'dd/mm/aaaa';
    input.inputMode = 'numeric';
    input.pattern = '[0-9]{2}/[0-9]{2}/[0-9]{4}';
    input.maxLength = 10;
    input.required = original.required;
    input.value = displayDate(original.value);
    input.id = original.name + '_display';
    const label = original.closest('.field').querySelector('label');
    if (label) label.htmlFor = input.id;
    original.before(input);
    original.type = 'hidden';
    original.required = false;
    input.addEventListener('input', () => {
      const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(input.value);
      let iso = '';
      if (match) {
        const candidate = `${match[3]}-${match[2]}-${match[1]}`;
        const date = new Date(candidate + 'T12:00:00Z');
        if (!Number.isNaN(date.getTime()) && date.toISOString().slice(0, 10) === candidate) iso = candidate;
      }
      input.setCustomValidity(iso ? '' : 'Ingresa una fecha válida en formato dd/mm/aaaa.');
      original.value = iso;
      original.dispatchEvent(new Event('change'));
    });
    return input;
  };
  localizedInput(issue);
  const expiryDisplay = localizedInput(expiry);
  const field = expiry.closest('.field');
  const automatic = () => {
    if (!issue.value) return '';
    const date = new Date(issue.value + 'T12:00:00Z');
    if (Number.isNaN(date.getTime())) return '';
    date.setUTCDate(date.getUTCDate() + 7);
    return date.toISOString().slice(0, 10);
  };
  const initial = expiry.value;
  const label = document.createElement('label');
  const checkbox = document.createElement('input');
  checkbox.type = 'checkbox';
  checkbox.name = 'custom_expiry';
  checkbox.value = '1';
  // Mantener los vencimientos personalizados de cotizaciones existentes.
  checkbox.checked = Boolean(initial && initial !== automatic());
  label.append(checkbox, ' Modificar vencimiento');
  const summary = document.createElement('small');
  summary.setAttribute('aria-live', 'polite');
  field.prepend(summary, label);
  const update = () => {
    expiry.disabled = !checkbox.checked;
    expiry.hidden = !checkbox.checked;
    expiryDisplay.disabled = !checkbox.checked;
    expiryDisplay.hidden = !checkbox.checked;
    expiryDisplay.required = checkbox.checked;
    if (!checkbox.checked) {
      expiry.value = automatic();
      expiryDisplay.value = displayDate(expiry.value);
      expiryDisplay.setCustomValidity('');
    }
    const date = displayDate(expiry.value);
    summary.textContent = checkbox.checked
      ? 'Vencimiento personalizado: ' + date
      : 'Vigencia: 7 días corridos desde la emisión. Vence el ' + date + '.';
  };
  checkbox.addEventListener('change', update);
  issue.addEventListener('change', update);
  expiry.addEventListener('change', update);
  update();
});

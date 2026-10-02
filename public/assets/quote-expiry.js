document.addEventListener('DOMContentLoaded', () => {
  const issue = document.querySelector('[name="issue_date"]');
  const expiry = document.querySelector('[name="expiry_date"]');
  if (!issue || !expiry) return;
  const field = expiry.closest('.field');
  const automatic = () => {
    if (!issue.value) return '';
    const date = new Date(issue.value + 'T12:00:00Z');
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
    expiry.required = checkbox.checked;
    if (!checkbox.checked) expiry.value = automatic();
    const date = expiry.value.split('-').reverse().join('/');
    summary.textContent = checkbox.checked
      ? 'Vencimiento personalizado: ' + date
      : 'Vigencia: 7 días corridos desde la emisión. Vence el ' + date + '.';
  };
  checkbox.addEventListener('change', update);
  issue.addEventListener('change', update);
  expiry.addEventListener('change', update);
  update();
});

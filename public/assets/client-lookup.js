document.addEventListener('DOMContentLoaded', () => {
  const rut = document.querySelector('[name="client_rut"]');
  if (!rut) return;
  const fields = {
    name: document.querySelector('[name="client_name"]'),
    email: document.querySelector('[name="client_email"]'),
    phone: document.querySelector('[name="client_phone"]'),
    address: document.querySelector('[name="client_address"]'),
    contact_name: document.querySelector('[name="attention_name"]')
  };
  const wrapper = rut.closest('.field');
  const status = document.createElement('small');
  status.className = 'client-lookup-status hint';
  wrapper?.appendChild(status);
  let timer;
  const lookup = () => {
    const value = rut.value.trim();
    if (!value) { status.textContent = ''; return; }
    status.textContent = 'Buscando cliente...';
    fetch('client_lookup.php?rut=' + encodeURIComponent(value), { credentials: 'same-origin' })
      .then(response => response.json())
      .then(data => {
        if (!data.found) { status.textContent = 'Cliente nuevo: completa sus datos.'; return; }
        const client = data.client;
        if (fields.name) fields.name.value = client.name || '';
        if (fields.email) fields.email.value = client.email || '';
        if (fields.phone) fields.phone.value = client.phone || '';
        if (fields.address) fields.address.value = client.address || '';
        if (fields.contact_name) fields.contact_name.value = client.contact_name || '';
        status.textContent = 'Cliente encontrado. Datos completados.';
      })
      .catch(() => { status.textContent = 'No fue posible buscar el cliente.'; });
  };
  rut.addEventListener('blur', lookup);
  rut.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(lookup, 500); });
});

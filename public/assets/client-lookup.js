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
  let revision = 0;
  const lookup = () => {
    const value = rut.value.trim();
    const current = ++revision;
    if (!value) { document.dispatchEvent(new CustomEvent('client-found',{detail:{contacts:[]}}));status.textContent = ''; return; }
    status.textContent = 'Buscando cliente...';
    fetch('client_lookup.php?rut=' + encodeURIComponent(value), { credentials: 'same-origin' })
      .then(response => { if(!response.ok) throw new Error('Búsqueda no disponible'); return response.json(); })
      .then(data => {
        if(current!==revision || value!==rut.value.trim()) return;
        if (!data.found) { if(fields.name)fields.name.readOnly=false;if(fields.address)fields.address.readOnly=false;document.dispatchEvent(new CustomEvent('client-found',{detail:{contacts:[]}}));status.textContent = 'Cliente nuevo: completa sus datos.'; return; }
        const client = data.client;
        if (fields.name) fields.name.value = client.name || '';
        if (fields.address) fields.address.value = client.address || '';
        if (fields.name) fields.name.readOnly = true;
        if (fields.address) fields.address.readOnly = true;
        document.dispatchEvent(new CustomEvent('client-found',{detail:data}));
        status.textContent = 'Cliente encontrado. Datos completados.';
      })
      .catch(() => { status.textContent = 'No fue posible buscar el cliente.'; });
  };
  rut.addEventListener('blur', lookup);
  rut.addEventListener('input', () => {
    ++revision;
    for(const field of [fields.name,fields.address]){if(field){field.value='';field.readOnly=false;}}
    document.dispatchEvent(new CustomEvent('client-found',{detail:{contacts:[]}}));
    clearTimeout(timer); timer = setTimeout(lookup, 500);
  });
});

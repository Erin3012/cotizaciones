document.addEventListener('DOMContentLoaded', () => {
  const rut = document.querySelector('[name="client_rut"]');
  const person = document.querySelector('[name="attention_name"]');
  if (!person) return;
  const form = person.form;
  const editing = Boolean(form.querySelector('[name="quote_id"]'));
  const fields = [person, form.querySelector('[name="client_email"]'), form.querySelector('[name="client_phone"]')];
  const areaLabel = document.createElement('label');
  areaLabel.textContent = 'Área *';
  areaLabel.htmlFor = 'contact-area';
  const area = document.createElement('input');
  area.className = 'input'; area.name = 'contact_area'; area.id = 'contact-area'; area.maxLength = 150;
  person.closest('.field').append(areaLabel, area);
  const label = document.createElement('label'); label.textContent = 'Destinatario de la cotización'; label.htmlFor = 'contact-selector';
  const selector = document.createElement('select'); selector.id = 'contact-selector';selector.className = 'select';
  const mode = document.createElement('input'); mode.type = 'hidden';mode.name = 'contact_mode';
  const contactId = document.createElement('input');contactId.type = 'hidden';contactId.name = 'contact_id';
  const status = document.createElement('small');status.setAttribute('aria-live','polite');
  const wrapper = document.createElement('div'); wrapper.className = 'field full';
  wrapper.append(label,selector,mode,contactId,status);person.closest('.form-grid').prepend(wrapper);
  let contacts=[];
  const originals=fields.map(input=>input.value);
  const apply = () => {
    mode.value=selector.value==='preserve'?'preserve':selector.value==='new'?'new':'existing';
    contactId.value=mode.value==='existing'?selector.value:'';
    const isNew=mode.value==='new';
    fields.forEach(input=>input.readOnly=!isNew);
    person.required=isNew;area.required=isNew;area.readOnly=!isNew;
    if(mode.value==='preserve'){fields.forEach((input,i)=>input.value=originals[i]);area.value=form.dataset.contactArea||'';status.textContent='Se conservarán los datos originales del destinatario y su área.';}
    else if(isNew){fields.forEach(input=>input.value='');area.value='';status.textContent='Completa persona y área. El contacto se guardará al guardar la cotización.';}
    else {const c=contacts.find(c=>String(c.id)===selector.value);if(c){person.value=c.name;area.value=c.area||'';fields[1].value=c.email||'';fields[2].value=c.phone||'';}status.textContent='Datos del contacto seleccionado. Para modificarlos, utiliza la ficha de clientes.';}
  };
  const render = list => {
    contacts=list;selector.replaceChildren();
    if(editing) selector.add(new Option('Conservar destinatario original','preserve'));
    selector.add(new Option('Agregar nuevo contacto','new'));
    contacts.forEach(c=>{const option=new Option(c.name+' — '+(c.area||'Área pendiente: completar en Clientes'),String(c.id));option.disabled=!c.area;selector.add(option);});
    selector.value=editing?'preserve':'new';apply();
  };
  selector.addEventListener('change',apply);
  render([]);
  document.addEventListener('client-found',event=>render(event.detail.contacts||[]));
  if(editing && form.dataset.clientRut){
    fetch('client_lookup.php?rut='+encodeURIComponent(form.dataset.clientRut)).then(r=>{if(!r.ok)throw Error();return r.json();}).then(data=>render(data.contacts||[])).catch(()=>{status.textContent='No se pudieron cargar los contactos. Puedes conservar el destinatario original.';});
  }
});

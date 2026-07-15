export function renderForm(payload, record = null) {
  const box = document.querySelector('#formContainer');
  const editing = record !== null;

  box.innerHTML = '';

  const form = document.createElement('form');
  form.id = 'recordForm';
  form.dataset.mode = editing ? 'edit' : 'add';

  if (editing) {
    form.dataset.pk = JSON.stringify(getPk(payload.primary, record));
  }

  const title = document.createElement('h2');
  title.textContent = editing ? 'Editar registro' : 'Insertar registro';
  form.appendChild(title);

  payload.fields.forEach((field) => {
    if (field.readonly) {
      return;
    }

    form.appendChild(createField(field, record, editing));
  });

  const actions = document.createElement('div');
  actions.className = 'form-actions';

  const submit = document.createElement('button');
  submit.type = 'submit';
  submit.textContent = editing ? 'Guardar' : 'Agregar';
  actions.appendChild(submit);

  if (editing) {
    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.textContent = 'Cancelar';
    cancel.id = 'cancelEdit';
    actions.appendChild(cancel);
  }

  form.appendChild(actions);
  box.appendChild(form);
}

export function clearForm(payload) {
  renderForm(payload);
}

export function getFormData(form) {
  const data = {};

  form.querySelectorAll('[name]').forEach((input) => {
    if (input.disabled) {
      return;
    }

    if (input.type === 'checkbox') {
      data[input.name] = input.checked ? 1 : 0;
      return;
    }

    data[input.name] = input.value;
  });

  if (form.dataset.pk) {
    data._pk = JSON.parse(form.dataset.pk);
  }

  return data;
}

function createField(field, record, editing) {
  const wrapper = document.createElement('label');
  const input = createInput(field);

  wrapper.className = 'form-field';
  wrapper.append(field.label, input);

  input.name = field.name;
  input.id = field.name;

  if (field.required && !(editing && field.input === 'password')) {
    input.required = true;
  }

  if (editing && field.primary) {
    input.disabled = true;
  }

  fillInput(input, field, record);

  return wrapper;
}

function createInput(field) {
  if (field.input === 'textarea') {
    return document.createElement('textarea');
  }

  if (field.input === 'select') {
    const select = document.createElement('select');

    field.options.forEach((optionValue) => {
      const option = document.createElement('option');
      option.value = optionValue;
      option.textContent = optionValue;
      select.appendChild(option);
    });

    return select;
  }

  const input = document.createElement('input');
  input.type = field.input;

  if (field.input === 'number') {
    input.step = 'any';
  }

  if (field.input === 'password') {
    input.placeholder = 'Escribe una nueva contraseña';
  }

  return input;
}

function fillInput(input, field, record) {
  if (!record || field.input === 'password') {
    return;
  }

  const value = record[field.name];

  if (field.input === 'checkbox') {
    input.checked = Number(value) === 1;
    return;
  }

  if (field.input === 'datetime-local' && typeof value === 'string') {
    input.value = value.replace(' ', 'T').slice(0, 16);
    return;
  }

  input.value = value ?? '';
}

function getPk(primaryFields, record) {
  const pk = {};

  primaryFields.forEach((field) => {
    pk[field] = record[field];
  });

  return pk;
}

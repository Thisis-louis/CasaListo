export function renderMenu(modules, activeTable) {
  const menu = document.querySelector('#moduleMenu');
  menu.innerHTML = '';

  Object.entries(modules).forEach(([table, title]) => {
    const link = document.createElement('a');
    link.href = `#/${table}`;
    link.textContent = title;

    if (table === activeTable) {
      link.classList.add('active');
    }

    menu.appendChild(link);
  });
}

export function renderTable(payload) {
  const head = document.querySelector('#tableHead');
  const body = document.querySelector('#tableBody');
  const count = document.querySelector('#recordCount');

  head.innerHTML = '';
  body.innerHTML = '';
  count.textContent = `${payload.records.length} registros`;

  payload.columns.forEach((column) => {
    const th = document.createElement('th');
    th.textContent = label(column);
    head.appendChild(th);
  });

  const actionTh = document.createElement('th');
  actionTh.textContent = 'Acciones';
  head.appendChild(actionTh);

  payload.records.forEach((record) => {
    const tr = document.createElement('tr');

    payload.columns.forEach((column) => {
      const td = document.createElement('td');
      td.textContent = value(record[column]);
      tr.appendChild(td);
    });

    const actions = document.createElement('td');
    actions.innerHTML = `
      <button type="button" data-action="edit">Editar</button>
      <button type="button" data-action="delete" class="danger">Eliminar</button>
    `;

    actions.querySelector('[data-action="edit"]').dataset.pk = JSON.stringify(primaryKey(payload.primary, record));
    actions.querySelector('[data-action="delete"]').dataset.pk = JSON.stringify(primaryKey(payload.primary, record));

    tr.appendChild(actions);
    body.appendChild(tr);
  });
}

export function showMessage(text, type = 'success') {
  const message = document.querySelector('#message');
  message.textContent = text;
  message.className = `module-message ${type}`;
  message.hidden = false;
}

export function hideMessage() {
  document.querySelector('#message').hidden = true;
}

export function setTitle(title) {
  document.querySelector('#moduleTitle').textContent = title;
}

export function findRecordByPk(records, pk) {
  return records.find((record) => {
    return Object.entries(pk).every(([field, value]) => String(record[field]) === String(value));
  });
}

export function primaryKey(fields, record) {
  const pk = {};

  fields.forEach((field) => {
    pk[field] = record[field];
  });

  return pk;
}

function value(data) {
  if (data === null || data === undefined || data === '') {
    return '-';
  }

  return String(data);
}

function label(column) {
  return column
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

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
  count.textContent = `${payload.pagination?.total_records ?? payload.records.length} registros`;

  payload.columns.forEach((column) => {
    const th = document.createElement('th');

    if ((payload.sortable_columns || []).includes(column)) {
      const button = document.createElement('button');
      const active = payload.sort === column;
      const direction = String(payload.dir || 'DESC').toLowerCase();

      button.type = 'button';
      button.dataset.sortColumn = column;
      button.textContent = `${label(column)} ${active ? (direction === 'asc' ? '↑' : '↓') : '↕'}`;
      th.appendChild(button);
    } else {
      th.textContent = label(column);
    }

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

export function renderSortOptions(payload) {
  const sort = document.querySelector('#moduleSort');
  const direction = document.querySelector('#moduleDirection');

  sort.innerHTML = '';

  (payload.sortable_columns || []).forEach((column) => {
    const option = document.createElement('option');
    option.value = column;
    option.textContent = label(column);
    sort.appendChild(option);
  });

  sort.value = payload.sort || '';
  direction.value = String(payload.dir || 'DESC').toLowerCase();
}

export function renderPagination(payload, onPage) {
  const root = document.querySelector('#modulePagination');
  const pagination = payload.pagination;

  root.innerHTML = '';

  if (!pagination) {
    return;
  }

  const summary = document.createElement('p');
  const actions = document.createElement('div');
  const previous = pageButton('Anterior', pagination.page - 1, !pagination.has_previous);
  const next = pageButton('Siguiente', pagination.page + 1, !pagination.has_next);

  summary.textContent = `${pagination.per_page} por página · Página ${pagination.page} de ${pagination.total_pages}`;
  actions.className = 'module-spa-page-actions';
  actions.appendChild(previous);

  pageRange(pagination.page, pagination.total_pages).forEach((page) => {
    const button = pageButton(String(page), page, false);

    if (page === pagination.page) {
      button.classList.add('is-active');
    }

    actions.appendChild(button);
  });

  actions.appendChild(next);
  root.append(summary, actions);

  root.querySelectorAll('[data-page]').forEach((button) => {
    button.addEventListener('click', () => onPage(Number(button.dataset.page)));
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

function pageButton(text, page, disabled) {
  const button = document.createElement('button');

  button.type = 'button';
  button.textContent = text;
  button.dataset.page = String(Math.max(1, page));
  button.disabled = disabled;

  return button;
}

function pageRange(current, total) {
  const start = Math.max(1, current - 2);
  const end = Math.min(total, current + 2);
  const pages = [];

  for (let page = start; page <= end; page += 1) {
    pages.push(page);
  }

  return pages;
}

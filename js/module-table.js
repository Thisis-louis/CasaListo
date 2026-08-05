const moduleRoot = document.querySelector('[data-module]');
const tableHead = document.querySelector('[data-table-head]');
const tableBody = document.querySelector('[data-table-body]');
const tableStatus = document.querySelector('[data-table-status]');
const tableShell = document.querySelector('.module-table-shell');
const moduleTitle = document.querySelector('[data-module-title]');
const moduleCount = document.querySelector('[data-module-count]');
const searchForm = document.querySelector('[data-search-form]');
const searchInput = document.querySelector('[data-search-input]');
const searchReset = document.querySelector('[data-search-reset]');
const serviceForm = document.querySelector('[data-service-form]');
const serviceFormTitle = document.querySelector('[data-service-form-title]');
const serviceSubmit = document.querySelector('[data-service-submit]');
const serviceReset = document.querySelector('[data-service-reset]');
const serviceCancel = document.querySelector('[data-service-cancel]');
const serviceMessage = document.querySelector('[data-service-message]');
const categorySelect = document.querySelector('[data-category-select]');

const serviceState = {
  csrfToken: '',
  records: [],
};

let sortSelect;
let directionSelect;
let paginationShell;
let currentPage = 1;

function formatColumnName(column) {
  return column
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatCellValue(value) {
  if (value === null || value === undefined || value === '') {
    return '-';
  }

  return String(value);
}

function isServicesModule() {
  return moduleRoot?.dataset.module === 'servicios';
}

function setStatus(message, type = 'loading') {
  if (!tableStatus) {
    return;
  }

  tableStatus.className = `module-${type}`;
  tableStatus.textContent = message;
  tableStatus.hidden = false;
}

function currentSearchValue() {
  return searchInput?.value.trim() || '';
}

function currentSortValue() {
  return sortSelect?.value || '';
}

function currentDirectionValue() {
  return directionSelect?.value || 'desc';
}

function moduleEndpointUrl() {
  const url = new URL(moduleRoot.dataset.endpoint, window.location.href);
  const search = currentSearchValue();
  const sort = currentSortValue();
  const direction = currentDirectionValue();

  if (search !== '') {
    url.searchParams.set('q', search);
  }

  if (sort !== '') {
    url.searchParams.set('sort', sort);
    url.searchParams.set('dir', direction);
  }

  url.searchParams.set('page', String(currentPage));

  return url;
}

function hideStatus() {
  if (tableStatus) {
    tableStatus.hidden = true;
  }
}

function setServiceMessage(message, type = 'success') {
  if (!serviceMessage) {
    return;
  }

  serviceMessage.className = `module-form-message module-form-message--${type}`;
  serviceMessage.textContent = message;
  serviceMessage.hidden = false;
}

function hideServiceMessage() {
  if (serviceMessage) {
    serviceMessage.hidden = true;
  }
}

function setServiceMode(mode) {
  const editing = mode === 'edit';

  if (serviceFormTitle) {
    serviceFormTitle.textContent = editing ? 'Editar servicio' : 'Insertar servicio';
  }

  if (serviceSubmit) {
    serviceSubmit.textContent = editing ? 'Guardar cambios' : 'Insertar';
  }

  if (serviceReset) {
    serviceReset.hidden = !editing;
  }

  if (serviceCancel) {
    serviceCancel.hidden = !editing;
  }
}

function resetServiceForm() {
  if (!serviceForm) {
    return;
  }

  serviceForm.reset();
  serviceForm.elements.id.value = '';
  serviceForm.elements.requiere_cotizacion.checked = true;
  setServiceMode('create');
  hideServiceMessage();
}

function populateCategoryOptions(categories = []) {
  if (!categorySelect) {
    return;
  }

  categorySelect.innerHTML = '<option value="">Selecciona</option>';

  categories.forEach((category) => {
    const option = document.createElement('option');
    option.value = category.id;
    option.textContent = category.nombre;
    categorySelect.appendChild(option);
  });
}

function servicePayloadFromForm() {
  const formData = new FormData(serviceForm);
  const payload = Object.fromEntries(formData.entries());

  payload.requiere_cotizacion = serviceForm.elements.requiere_cotizacion.checked ? 1 : 0;
  payload.destacado = serviceForm.elements.destacado.checked ? 1 : 0;

  return payload;
}

function fillServiceForm(record) {
  serviceForm.elements.id.value = record.id ?? '';
  serviceForm.elements.categoria_id.value = record.categoria_id ?? '';
  serviceForm.elements.nombre.value = record.nombre ?? '';
  serviceForm.elements.slug.value = record.slug ?? '';
  serviceForm.elements.descripcion.value = record.descripcion ?? '';
  serviceForm.elements.precio_base.value = record.precio_base ?? '';
  serviceForm.elements.estado.value = record.estado ?? 'activo';
  serviceForm.elements.requiere_cotizacion.checked = Number(record.requiere_cotizacion) === 1;
  serviceForm.elements.destacado.checked = Number(record.destacado) === 1;
  setServiceMode('edit');
  hideServiceMessage();
  serviceForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderActionCell(row, record) {
  const td = document.createElement('td');
  const wrapper = document.createElement('div');
  const editLink = document.createElement('a');
  const deleteLink = document.createElement('a');

  wrapper.className = 'module-actions';
  editLink.href = `?editar=${record.id}`;
  editLink.textContent = 'Editar';
  editLink.dataset.action = 'edit';
  editLink.dataset.id = record.id;

  deleteLink.href = `?eliminar=${record.id}`;
  deleteLink.textContent = 'Eliminar';
  deleteLink.dataset.action = 'delete';
  deleteLink.dataset.id = record.id;
  deleteLink.className = 'module-action-danger';

  wrapper.append(editLink, deleteLink);
  td.appendChild(wrapper);
  row.appendChild(td);
}

function createFilterSelect(id, label, options) {
  const field = document.createElement('div');
  const labelElement = document.createElement('label');
  const select = document.createElement('select');

  field.className = 'module-filter-field';
  labelElement.htmlFor = id;
  labelElement.textContent = label;
  select.className = 'cl-select';
  select.id = id;

  options.forEach((optionData) => {
    const option = document.createElement('option');
    option.value = optionData.value;
    option.textContent = optionData.label;
    select.appendChild(option);
  });

  field.append(labelElement, select);

  return { field, select };
}

function ensureSortControls(payload) {
  if (!searchForm || sortSelect || directionSelect) {
    return;
  }

  const sortableColumns = payload.sortable_columns || [];

  if (sortableColumns.length < 3) {
    return;
  }

  const sortControl = createFilterSelect(
    'module-sort',
    'Ordenar por',
    sortableColumns.map((column) => ({
      value: column,
      label: formatColumnName(column),
    })),
  );
  const directionControl = createFilterSelect('module-direction', 'Dirección', [
    { value: 'asc', label: 'Ascendente' },
    { value: 'desc', label: 'Descendente' },
  ]);
  const submitButton = searchForm.querySelector('button[type="submit"]');

  sortSelect = sortControl.select;
  directionSelect = directionControl.select;

  searchForm.insertBefore(sortControl.field, submitButton);
  searchForm.insertBefore(directionControl.field, submitButton);

  sortSelect.addEventListener('change', () => {
    currentPage = 1;
    loadModuleTable();
  });
  directionSelect.addEventListener('change', () => {
    currentPage = 1;
    loadModuleTable();
  });
}

function setSortControls(payload) {
  ensureSortControls(payload);

  if (sortSelect && payload.sort) {
    sortSelect.value = payload.sort;
  }

  if (directionSelect && payload.dir) {
    directionSelect.value = payload.dir.toLowerCase();
  }
}

function toggleSort(column) {
  if (!sortSelect || !directionSelect) {
    return;
  }

  if (sortSelect.value === column) {
    directionSelect.value = directionSelect.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortSelect.value = column;
    directionSelect.value = 'asc';
  }

  currentPage = 1;
  loadModuleTable();
}

function ensurePaginationShell() {
  if (paginationShell || !tableShell) {
    return;
  }

  paginationShell = document.createElement('nav');
  paginationShell.className = 'module-pagination';
  paginationShell.setAttribute('aria-label', 'Paginación de registros');
  tableShell.after(paginationShell);
}

function paginationRange(current, total) {
  const pages = new Set([1, total]);
  const start = Math.max(1, current - 2);
  const end = Math.min(total, current + 2);

  for (let page = start; page <= end; page += 1) {
    pages.add(page);
  }

  return [...pages].sort((a, b) => a - b);
}

function createPageButton(label, page, options = {}) {
  const button = document.createElement('button');

  button.type = 'button';
  button.className = `module-page-button${options.active ? ' is-active' : ''}`;
  button.textContent = label;
  button.disabled = Boolean(options.disabled);
  button.dataset.page = String(page);

  return button;
}

function renderPagination(payload) {
  ensurePaginationShell();

  if (!paginationShell) {
    return;
  }

  const pagination = payload.pagination;

  if (!pagination) {
    paginationShell.hidden = true;
    return;
  }

  currentPage = pagination.page;
  paginationShell.hidden = false;
  paginationShell.innerHTML = '';

  const summary = document.createElement('p');
  const controls = document.createElement('div');
  const totalPages = pagination.total_pages;
  const totalRecords = pagination.total_records;

  summary.className = 'module-pagination-summary';
  summary.textContent = `${totalRecords} registro${totalRecords === 1 ? '' : 's'} · ${pagination.per_page} por página · Página ${pagination.page} de ${totalPages}`;
  controls.className = 'module-pagination-controls';

  controls.appendChild(createPageButton('Anterior', Math.max(1, pagination.page - 1), {
    disabled: !pagination.has_previous,
  }));

  let previousPage = 0;
  paginationRange(pagination.page, totalPages).forEach((page) => {
    if (previousPage && page - previousPage > 1) {
      const gap = document.createElement('span');
      gap.className = 'module-page-gap';
      gap.textContent = '...';
      controls.appendChild(gap);
    }

    controls.appendChild(createPageButton(String(page), page, {
      active: page === pagination.page,
    }));
    previousPage = page;
  });

  controls.appendChild(createPageButton('Siguiente', Math.min(totalPages, pagination.page + 1), {
    disabled: !pagination.has_next,
  }));

  paginationShell.append(summary, controls);
}

function renderTable(payload) {
  const columns = payload.columns || [];
  const records = payload.records || [];
  const sortableColumns = payload.sortable_columns || [];
  const withActions = isServicesModule() && payload.actions;

  setSortControls(payload);

  if (isServicesModule()) {
    serviceState.records = records;
    serviceState.csrfToken = payload.csrf_token || serviceState.csrfToken;
    populateCategoryOptions(payload.categorias || []);
  }

  if (moduleTitle && payload.title) {
    moduleTitle.textContent = payload.title;
  }

  if (searchInput && payload.search !== undefined && searchInput.value !== payload.search) {
    searchInput.value = payload.search;
  }

  if (moduleCount) {
    const totalRecords = payload.pagination?.total_records ?? records.length;
    const suffix = payload.search ? ' encontrados' : '';
    moduleCount.textContent = `${totalRecords} registro${totalRecords === 1 ? '' : 's'}${suffix}`;
  }

  tableHead.innerHTML = '';
  tableBody.innerHTML = '';

  if (!records.length) {
    setStatus(payload.search ? 'No hay registros que coincidan con la búsqueda.' : 'No hay registros para mostrar.', 'empty');
    renderPagination(payload);
    return;
  }

  const headRow = document.createElement('tr');
  columns.forEach((column) => {
    const th = document.createElement('th');
    const isSortable = sortableColumns.includes(column);

    if (isSortable) {
      const button = document.createElement('button');
      const isActive = payload.sort === column;
      const direction = String(payload.dir || 'DESC').toLowerCase();

      button.type = 'button';
      button.className = 'module-sort-button';
      button.dataset.sortColumn = column;
      button.textContent = `${formatColumnName(column)} ${isActive ? (direction === 'asc' ? '↑' : '↓') : '↕'}`;
      th.appendChild(button);
    } else {
      th.textContent = formatColumnName(column);
    }

    headRow.appendChild(th);
  });

  if (withActions) {
    const th = document.createElement('th');
    th.textContent = 'Acciones';
    headRow.appendChild(th);
  }

  tableHead.appendChild(headRow);

  records.forEach((record) => {
    const row = document.createElement('tr');

    columns.forEach((column) => {
      const td = document.createElement('td');
      td.textContent = formatCellValue(record[column]);
      row.appendChild(td);
    });

    if (withActions) {
      renderActionCell(row, record);
    }

    tableBody.appendChild(row);
  });

  hideStatus();
  renderPagination(payload);
}

async function loadModuleTable() {
  if (!moduleRoot) {
    return;
  }

  setStatus('Cargando registros...');

  try {
    const response = await fetch(moduleEndpointUrl(), {
      headers: {
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const payload = await response.json();

    if (!payload.ok) {
      throw new Error(payload.message || 'No se pudieron cargar los registros.');
    }

    renderTable(payload);
  } catch (error) {
    setStatus('No se pudieron cargar los registros de este módulo.', 'error');
  }
}

loadModuleTable();

if (searchForm) {
  let searchTimer;

  searchForm.addEventListener('submit', (event) => {
    event.preventDefault();
    currentPage = 1;
    loadModuleTable();
  });

  searchInput?.addEventListener('input', () => {
    window.clearTimeout(searchTimer);
    currentPage = 1;
    searchTimer = window.setTimeout(loadModuleTable, 320);
  });
}

if (searchReset) {
  searchReset.addEventListener('click', () => {
    if (searchInput) {
      searchInput.value = '';
    }

    currentPage = 1;
    loadModuleTable();
  });
}

if (paginationShell || tableShell) {
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-page]');

    if (!button || !button.closest('.module-pagination') || button.disabled) {
      return;
    }

    currentPage = Number(button.dataset.page) || 1;
    loadModuleTable();
  });
}

if (tableHead) {
  tableHead.addEventListener('click', (event) => {
    const button = event.target.closest('[data-sort-column]');

    if (!button) {
      return;
    }

    toggleSort(button.dataset.sortColumn);
  });
}

if (serviceForm) {
  serviceForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    hideServiceMessage();

    const payload = servicePayloadFromForm();
    const method = payload.id ? 'PUT' : 'POST';

    try {
      const response = await fetch(moduleRoot.dataset.endpoint, {
        method,
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-Token': serviceState.csrfToken,
        },
        body: JSON.stringify(payload),
      });
      const result = await response.json();

      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'No se pudo guardar el servicio.');
      }

      setServiceMessage(result.message);
      resetServiceForm();
      await loadModuleTable();
      setServiceMessage(result.message);
    } catch (error) {
      setServiceMessage(error.message || 'No se pudo guardar el servicio.', 'error');
    }
  });

  serviceForm.elements.nombre.addEventListener('blur', () => {
    if (serviceForm.elements.slug.value.trim() !== '') {
      return;
    }

    serviceForm.elements.slug.value = serviceForm.elements.nombre.value
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  });
}

if (serviceReset) {
  serviceReset.addEventListener('click', resetServiceForm);
}

if (serviceCancel) {
  serviceCancel.addEventListener('click', resetServiceForm);
}

if (tableBody && isServicesModule()) {
  tableBody.addEventListener('click', async (event) => {
    const link = event.target.closest('[data-action]');

    if (!link) {
      return;
    }

    event.preventDefault();

    const id = link.dataset.id;
    const record = serviceState.records.find((item) => String(item.id) === String(id));

    if (link.dataset.action === 'edit' && record) {
      fillServiceForm(record);
      return;
    }

    if (link.dataset.action !== 'delete') {
      return;
    }

    if (!record || !confirm(`¿Eliminar el servicio "${record.nombre}"?`)) {
      return;
    }

    try {
      const response = await fetch(moduleRoot.dataset.endpoint, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-Token': serviceState.csrfToken,
        },
        body: JSON.stringify({ id }),
      });
      const result = await response.json();

      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'No se pudo eliminar el servicio.');
      }

      if (serviceForm?.elements.id.value === String(id)) {
        resetServiceForm();
      }

      await loadModuleTable();
      setServiceMessage(result.message);
    } catch (error) {
      setServiceMessage(error.message || 'No se pudo eliminar el servicio.', 'error');
    }
  });
}

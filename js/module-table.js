const moduleRoot = document.querySelector('[data-module]');
const tableHead = document.querySelector('[data-table-head]');
const tableBody = document.querySelector('[data-table-body]');
const tableStatus = document.querySelector('[data-table-status]');
const moduleTitle = document.querySelector('[data-module-title]');
const moduleCount = document.querySelector('[data-module-count]');
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

function renderTable(payload) {
  const columns = payload.columns || [];
  const records = payload.records || [];
  const withActions = isServicesModule() && payload.actions;

  if (isServicesModule()) {
    serviceState.records = records;
    serviceState.csrfToken = payload.csrf_token || serviceState.csrfToken;
    populateCategoryOptions(payload.categorias || []);
  }

  if (moduleTitle && payload.title) {
    moduleTitle.textContent = payload.title;
  }

  if (moduleCount) {
    moduleCount.textContent = `${records.length} registro${records.length === 1 ? '' : 's'}`;
  }

  tableHead.innerHTML = '';
  tableBody.innerHTML = '';

  if (!records.length) {
    setStatus('No hay registros para mostrar.', 'empty');
    return;
  }

  const headRow = document.createElement('tr');
  columns.forEach((column) => {
    const th = document.createElement('th');
    th.textContent = formatColumnName(column);
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
}

async function loadModuleTable() {
  if (!moduleRoot) {
    return;
  }

  const endpoint = moduleRoot.dataset.endpoint;
  setStatus('Cargando registros...');

  try {
    const response = await fetch(endpoint, {
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

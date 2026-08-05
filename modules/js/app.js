import { addRecord, deleteRecord, editRecord, getRecords } from './api.js';
import { clearForm, getFormData, renderForm } from './form.js';
import { findRecordByPk, renderMenu, renderPagination, renderSortOptions, renderTable, setTitle, showMessage } from './renders.js';
import { showForm, showList } from './views.js';

const state = {
  table: '',
  payload: null,
  search: '',
  sort: '',
  dir: 'desc',
  page: 1,
};

start();

function start() {
  window.addEventListener('hashchange', loadCurrentModule);
  document.querySelector('#addBtn').addEventListener('click', openAddForm);
  document.querySelector('#listBtn').addEventListener('click', showList);
  document.querySelector('#tableBody').addEventListener('click', tableClick);
  document.querySelector('#tableHead').addEventListener('click', tableHeadClick);
  document.querySelector('#formContainer').addEventListener('submit', saveForm);
  document.querySelector('#formContainer').addEventListener('click', cancelEdit);
  document.querySelector('#moduleFilters').addEventListener('submit', filterSubmit);
  document.querySelector('#moduleSearch').addEventListener('input', filterInput);
  document.querySelector('#moduleSort').addEventListener('change', filterSubmit);
  document.querySelector('#moduleDirection').addEventListener('change', filterSubmit);
  document.querySelector('#clearFilters').addEventListener('click', clearFilters);

  loadCurrentModule();
}

async function loadCurrentModule() {
  const table = currentRoute();

  try {
    if (state.table !== table) {
      state.search = '';
      state.sort = '';
      state.dir = 'desc';
      state.page = 1;
      document.querySelector('#moduleSearch').value = '';
    }

    state.table = table;
    state.payload = await getRecords(table, {
      q: state.search,
      sort: state.sort,
      dir: state.dir,
      page: state.page,
    });

    setTitle(state.payload.title);
    renderMenu(state.payload.modules, table);
    renderSortOptions(state.payload);
    state.sort = state.payload.sort;
    state.dir = String(state.payload.dir || 'DESC').toLowerCase();
    renderTable(state.payload);
    renderPagination(state.payload, goToPage);
    clearForm(state.payload);
    showList();
  } catch (error) {
    showMessage(error.message, 'error');
  }
}

function filterSubmit(event) {
  event?.preventDefault();
  state.search = document.querySelector('#moduleSearch').value.trim();
  state.sort = document.querySelector('#moduleSort').value;
  state.dir = document.querySelector('#moduleDirection').value;
  state.page = 1;
  loadCurrentModule();
}

let filterTimer;

function filterInput() {
  window.clearTimeout(filterTimer);
  filterTimer = window.setTimeout(filterSubmit, 320);
}

function clearFilters() {
  document.querySelector('#moduleSearch').value = '';
  state.search = '';
  state.page = 1;
  loadCurrentModule();
}

function goToPage(page) {
  state.page = page;
  loadCurrentModule();
}

function currentRoute() {
  const table = location.hash.replace('#/', '');

  if (table) {
    return table;
  }

  if (document.body.dataset.module) {
    return document.body.dataset.module;
  }

  return 'roles';
}

function openAddForm() {
  renderForm(state.payload);
  showForm();
}

async function saveForm(event) {
  event.preventDefault();

  const form = event.target;
  const data = getFormData(form);

  try {
    if (form.dataset.mode === 'edit') {
      await editRecord(state.table, data, state.payload.csrf_token);
      showMessage('Registro editado correctamente.');
    } else {
      await addRecord(state.table, data, state.payload.csrf_token);
      showMessage('Registro insertado correctamente.');
    }

    await loadCurrentModule();
  } catch (error) {
    showMessage(error.message, 'error');
  }
}

async function tableClick(event) {
  const button = event.target.closest('[data-action]');

  if (!button) {
    return;
  }

  const pk = JSON.parse(button.dataset.pk);
  const record = findRecordByPk(state.payload.records, pk);

  if (button.dataset.action === 'edit') {
    renderForm(state.payload, record);
    showForm();
    return;
  }

  if (!confirm('¿Seguro que quieres eliminar este registro?')) {
    return;
  }

  try {
    await deleteRecord(state.table, { _pk: pk }, state.payload.csrf_token);
    showMessage('Registro eliminado correctamente.');
    await loadCurrentModule();
  } catch (error) {
    showMessage(error.message, 'error');
  }
}

function tableHeadClick(event) {
  const button = event.target.closest('[data-sort-column]');

  if (!button) {
    return;
  }

  if (state.sort === button.dataset.sortColumn) {
    state.dir = state.dir === 'asc' ? 'desc' : 'asc';
  } else {
    state.sort = button.dataset.sortColumn;
    state.dir = 'asc';
  }

  document.querySelector('#moduleSort').value = state.sort;
  document.querySelector('#moduleDirection').value = state.dir;
  state.page = 1;
  loadCurrentModule();
}

function cancelEdit(event) {
  if (event.target.id === 'cancelEdit') {
    showList();
  }
}

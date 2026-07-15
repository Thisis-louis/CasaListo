import { addRecord, deleteRecord, editRecord, getRecords } from './api.js';
import { clearForm, getFormData, renderForm } from './form.js';
import { findRecordByPk, renderMenu, renderTable, setTitle, showMessage } from './renders.js';
import { showForm, showList } from './views.js';

const state = {
  table: '',
  payload: null,
};

start();

function start() {
  window.addEventListener('hashchange', loadCurrentModule);
  document.querySelector('#addBtn').addEventListener('click', openAddForm);
  document.querySelector('#listBtn').addEventListener('click', showList);
  document.querySelector('#tableBody').addEventListener('click', tableClick);
  document.querySelector('#formContainer').addEventListener('submit', saveForm);
  document.querySelector('#formContainer').addEventListener('click', cancelEdit);

  loadCurrentModule();
}

async function loadCurrentModule() {
  const table = currentRoute();

  try {
    state.table = table;
    state.payload = await getRecords(table);

    setTitle(state.payload.title);
    renderMenu(state.payload.modules, table);
    renderTable(state.payload);
    clearForm(state.payload);
    showList();
  } catch (error) {
    showMessage(error.message, 'error');
  }
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

function cancelEdit(event) {
  if (event.target.id === 'cancelEdit') {
    showList();
  }
}

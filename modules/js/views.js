export function showList() {
  document.querySelector('#tableList').classList.remove('hidden');
  document.querySelector('#formContainer').classList.add('hidden');
  document.querySelector('#listBtn').classList.add('hidden');
  document.querySelector('#addBtn').classList.remove('hidden');
}

export function showForm() {
  document.querySelector('#tableList').classList.add('hidden');
  document.querySelector('#formContainer').classList.remove('hidden');
  document.querySelector('#listBtn').classList.remove('hidden');
  document.querySelector('#addBtn').classList.add('hidden');
}

const API_URL = '../php/api.php';

export async function getModules() {
  const response = await fetch(API_URL);
  return readResponse(response);
}

export async function getRecords(table) {
  const response = await fetch(`${API_URL}?table=${table}`);
  return readResponse(response);
}

export async function addRecord(table, data, token) {
  const response = await fetch(`${API_URL}?table=${table}`, {
    method: 'POST',
    headers: jsonHeaders(token),
    body: JSON.stringify(data),
  });

  return readResponse(response);
}

export async function editRecord(table, data, token) {
  const response = await fetch(`${API_URL}?table=${table}`, {
    method: 'PUT',
    headers: jsonHeaders(token),
    body: JSON.stringify(data),
  });

  return readResponse(response);
}

export async function deleteRecord(table, data, token) {
  const response = await fetch(`${API_URL}?table=${table}`, {
    method: 'DELETE',
    headers: jsonHeaders(token),
    body: JSON.stringify(data),
  });

  return readResponse(response);
}

function jsonHeaders(token) {
  return {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-Token': token,
  };
}

async function readResponse(response) {
  const data = await response.json();

  if (!response.ok || !data.ok) {
    throw new Error(data.message || 'No se pudo completar la acción.');
  }

  return data;
}

const ENDPOINT = "../php/solicitudes.php";

async function request(action, payload = {}) {
    const response = await fetch(ENDPOINT, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            action,
            ...payload
        })
    });

    const rawText = await response.text();

    let data;
    try {
        data = rawText ? JSON.parse(rawText) : null;
    } catch {
        console.error("Respuesta cruda del servidor:", rawText);
        throw new Error("El servidor no devolvió JSON válido.");
    }

    if (!response.ok || !data || data.ok === false) {
        throw new Error(data?.message || "Ocurrió un error al procesar la solicitud.");
    }

    return data;
}

async function getAll() {
    return request("getAll");
}

async function getOne(id) {
    return request("getOne", { id });
}

async function create(payload) {
    return request("create", payload);
}

async function update(id, payload) {
    return request("update", { id, ...payload });
}

async function deleteRecord(id) {
    return request("delete", { id });
}

export {
    getAll,
    getOne,
    create,
    update,
    deleteRecord
};
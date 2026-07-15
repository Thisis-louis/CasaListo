import {
    getAll,
    getOne,
    create,
    update,
    deleteRecord,
    getClientes,
    getServicios
} from "./api_solicitudes.js";

const state = {
    editingId: null,
    columns: [],
    foreignKeysLoaded: false,
    foreignKeysPromise: null
};

document.addEventListener("DOMContentLoaded", () => {
    showTable();
    bindEvents();
    configurarFechaMinima();
    cargarSolicitudes();
});

function configurarFechaMinima() {
    const fechaInput = document.getElementById("fecha_preferida");

    if (!fechaInput) {
        return;
    }

    const hoy = new Date();
    hoy.setMinutes(hoy.getMinutes() - hoy.getTimezoneOffset());
    const fechaMinima = hoy.toISOString().split("T")[0];

    fechaInput.min = fechaMinima;
}

function bindEvents() {
    const addBtn = document.querySelector("[data-add-btn]");
    const cancelBtn = document.querySelector("[data-cancel-btn]");
    const form = document.getElementById("solicitudForm");
    const resetBtn = form?.querySelector('button[type="reset"]');

    if (addBtn) {
        addBtn.addEventListener("click", () => {
            openCreateMode();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", closeFormAndReturnToTable);
    }

    if (form) {
        form.addEventListener("submit", handleSubmit);
    }

    if (resetBtn) {
        resetBtn.addEventListener("click", (event) => {
            event.preventDefault();

            if (state.editingId !== null) {
                closeFormAndReturnToTable();
                return;
            }

            resetFormDefaults();
        });
    }
}

async function cargarSolicitudes() {
    const statusEl = document.querySelector("[data-table-status]");
    const countEl = document.querySelector("[data-module-count]");
    const titleEl = document.querySelector("[data-module-title]");
    const thead = document.querySelector("[data-table-head]");
    const tbody = document.querySelector("[data-table-body]");

    if (!statusEl || !countEl || !titleEl || !thead || !tbody) {
        console.error("No se encontraron los elementos necesarios para renderizar la tabla.");
        return;
    }

    try {
        const json = await getAll();

        if (!json.ok) {
            throw new Error(json.message || "No se pudieron cargar las solicitudes.");
        }

        state.columns = Array.isArray(json.columns) ? json.columns : [];
        const records = Array.isArray(json.records) ? json.records : [];

        titleEl.textContent = json.title || "Solicitudes";
        countEl.textContent = `${records.length} registros`;
        statusEl.textContent = records.length > 0
            ? "Registros cargados correctamente."
            : "No hay registros para mostrar.";

        renderTable(thead, tbody, state.columns, records);
    } catch (error) {
        const message = error instanceof Error ? error.message : "Ocurrió un error inesperado.";

        statusEl.textContent = message;
        countEl.textContent = "0 registros";
        thead.innerHTML = "";
        tbody.innerHTML = `
            <tr>
                <td class="px-5 py-6 text-center text-gray-500" colspan="1">
                    No fue posible cargar la información.
                </td>
            </tr>
        `;

        if (window.Swal) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: message
            });
        }

        console.error(error);
    }
}

function renderTable(thead, tbody, columns, records) {
    thead.innerHTML = "";
    tbody.innerHTML = "";

    const headerRow = document.createElement("tr");

    columns.forEach((column) => {
        const th = document.createElement("th");
        th.className = "px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider";
        th.textContent = formatHeader(column);
        headerRow.appendChild(th);
    });

    const actionsTh = document.createElement("th");
    actionsTh.className = "px-5 py-4 text-center text-xs font-semibold uppercase tracking-wider";
    actionsTh.textContent = "Acciones";
    headerRow.appendChild(actionsTh);

    thead.appendChild(headerRow);

    if (records.length === 0) {
        const emptyRow = document.createElement("tr");
        const emptyCell = document.createElement("td");

        emptyCell.colSpan = Math.max(columns.length + 1, 1);
        emptyCell.className = "px-5 py-6 text-center text-gray-500";
        emptyCell.textContent = "No hay registros disponibles.";

        emptyRow.appendChild(emptyCell);
        tbody.appendChild(emptyRow);
        return;
    }

    records.forEach((record) => {
        const row = document.createElement("tr");
        row.className = "border-b border-gray-200 hover:bg-gray-50 transition-colors";

        columns.forEach((column) => {
            const td = document.createElement("td");
            td.className = "px-5 py-4 text-gray-700 align-top";
            td.textContent = formatValue(record, column);
            row.appendChild(td);
        });

        const actionsTd = document.createElement("td");
        actionsTd.className = "px-5 py-4";

        const wrapper = document.createElement("div");
        wrapper.className = "flex items-center justify-center gap-2";

        const editBtn = document.createElement("button");
        editBtn.type = "button";
        editBtn.className = "w-10 h-10 rounded border border-gray-300 hover:bg-gray-100 transition";
        editBtn.title = "Editar";
        editBtn.textContent = "✏️";
        editBtn.addEventListener("click", () => openEditMode(record?.id));

        const deleteBtn = document.createElement("button");
        deleteBtn.type = "button";
        deleteBtn.className = "w-10 h-10 rounded border border-gray-300 hover:bg-gray-100 transition";
        deleteBtn.title = "Eliminar";
        deleteBtn.textContent = "🗑️";
        deleteBtn.addEventListener("click", () => eliminarSolicitud(record?.id));

        wrapper.appendChild(editBtn);
        wrapper.appendChild(deleteBtn);
        actionsTd.appendChild(wrapper);
        row.appendChild(actionsTd);

        tbody.appendChild(row);
    });
}

function formatHeader(column) {
    const labels = {
        id: "ID",
        cliente_id: "Cliente",
        servicio_id: "Servicio",
        folio: "Folio",
        titulo: "Título",
        descripcion: "Descripción",
        direccion: "Dirección",
        colonia: "Colonia",
        ciudad: "Ciudad",
        estado_region: "Estado región",
        codigo_postal: "Código postal",
        fecha_preferida: "Fecha preferida",
        hora_preferida: "Hora preferida",
        urgencia: "Urgencia",
        estado: "Estado",
        creado_en: "Creado en",
        actualizado_en: "Actualizado en"
    };

    return labels[column] || column;
}

function formatValue(record, column) {
    if (!record) {
        return "";
    }

    if (column === "cliente_id") {
        return record.cliente_nombre ?? record.cliente_label ?? record.cliente_id ?? "";
    }

    if (column === "servicio_id") {
        return record.servicio_nombre ?? record.servicio_label ?? record.servicio_id ?? "";
    }

    const value = record[column];

    if (value === null || value === undefined) {
        return "";
    }

    if (typeof value === "object") {
        return JSON.stringify(value);
    }

    return String(value);
}

function showTable() {
    const tableContainer = document.querySelector("[data-table-container]");
    const formContainer = document.querySelector("[data-form-container]");

    if (tableContainer) {
        tableContainer.hidden = false;
    }

    if (formContainer) {
        formContainer.hidden = true;
    }
}

function showForm() {
    const tableContainer = document.querySelector("[data-table-container]");
    const formContainer = document.querySelector("[data-form-container]");

    if (tableContainer) {
        tableContainer.hidden = true;
    }

    if (formContainer) {
        formContainer.hidden = false;
    }
}

async function loadForeignKeyOptions() {
    if (state.foreignKeysLoaded) {
        return;
    }

    if (!state.foreignKeysPromise) {
        state.foreignKeysPromise = Promise.all([
            getClientes(),
            getServicios()
        ])
            .then(([clientesJson, serviciosJson]) => {
                populateSelect(
                    "cliente_id",
                    Array.isArray(clientesJson.options) ? clientesJson.options : [],
                    "Seleccione un cliente"
                );

                populateSelect(
                    "servicio_id",
                    Array.isArray(serviciosJson.options) ? serviciosJson.options : [],
                    "Seleccione un servicio"
                );

                state.foreignKeysLoaded = true;
            })
            .finally(() => {
                state.foreignKeysPromise = null;
            });
    }

    return state.foreignKeysPromise;
}

function populateSelect(selectId, options, placeholder) {
    const select = document.getElementById(selectId);

    if (!select) {
        return;
    }

    const currentValue = select.value;
    select.innerHTML = "";

    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = placeholder;
    select.appendChild(defaultOption);

    options.forEach((option) => {
        const opt = document.createElement("option");
        opt.value = String(option.id ?? "");
        opt.textContent = String(option.label ?? option.nombre ?? option.descripcion ?? option.id ?? "");
        select.appendChild(opt);
    });

    if (currentValue) {
        select.value = currentValue;
    }
}

function resetFormDefaults() {
    const form = document.getElementById("solicitudForm");

    if (form) {
        form.reset();
    }

    const idInput = document.getElementById("id");
    if (idInput) {
        idInput.value = "";
    }

    const urgencia = document.getElementById("urgencia");
    const estado = document.getElementById("estado");
    const cliente = document.getElementById("cliente_id");
    const servicio = document.getElementById("servicio_id");

    if (cliente) cliente.value = "";
    if (servicio) servicio.value = "";
    if (urgencia) urgencia.value = "normal";
    if (estado) estado.value = "nueva";

    state.editingId = null;
}

async function openCreateMode() {
    try {
        await loadForeignKeyOptions();
        resetFormDefaults();

        const formMode = document.querySelector("[data-form-mode]");
        const formTitle = document.querySelector("[data-form-title]");
        const saveBtn = document.querySelector("[data-save-btn]");

        if (formMode) {
            formMode.textContent = "Nueva solicitud";
        }

        if (formTitle) {
            formTitle.textContent = "Registrar solicitud";
        }

        if (saveBtn) {
            saveBtn.textContent = "Guardar solicitud";
        }

        showForm();
    } catch (error) {
        const message = error instanceof Error ? error.message : "No se pudo preparar el formulario.";

        Swal.fire({
            icon: "error",
            title: "Error",
            text: message
        });

        console.error(error);
    }
}

async function openEditMode(id) {
    if (id === undefined || id === null || id === "") {
        return;
    }

    try {
        const json = await getOne(id);

        if (!json.ok || !json.record) {
            throw new Error(json.message || "No se pudo cargar la solicitud.");
        }

        await loadForeignKeyOptions();
        fillForm(json.record);

        const formMode = document.querySelector("[data-form-mode]");
        const formTitle = document.querySelector("[data-form-title]");
        const saveBtn = document.querySelector("[data-save-btn]");

        if (formMode) {
            formMode.textContent = "Editando solicitud";
        }

        if (formTitle) {
            formTitle.textContent = `Editar solicitud #${id}`;
        }

        if (saveBtn) {
            saveBtn.textContent = "Actualizar solicitud";
        }

        state.editingId = id;
        showForm();
    } catch (error) {
        const message = error instanceof Error ? error.message : "No se pudo cargar la solicitud.";

        Swal.fire({
            icon: "error",
            title: "Error",
            text: message
        });

        console.error(error);
    }
}

function closeFormAndReturnToTable() {
    resetFormDefaults();
    showTable();
    cargarSolicitudes();
}

function fillForm(record) {
    const fields = [
        "id",
        "cliente_id",
        "servicio_id",
        "folio",
        "titulo",
        "descripcion",
        "direccion",
        "colonia",
        "ciudad",
        "estado_region",
        "codigo_postal",
        "fecha_preferida",
        "hora_preferida",
        "urgencia",
        "estado"
    ];

    fields.forEach((field) => {
        const input = document.getElementById(field);
        if (input) {
            input.value = record?.[field] ?? "";
        }
    });
}

async function eliminarSolicitud(id) {
    if (id === undefined || id === null || id === "") {
        return;
    }

    const confirmacion = await Swal.fire({
        icon: "warning",
        title: "¿Eliminar solicitud?",
        text: "Esta acción no se puede deshacer.",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33"
    });

    if (!confirmacion.isConfirmed) {
        return;
    }

    try {
        const result = await deleteRecord(id);

        await Swal.fire({
            icon: "success",
            title: "Eliminada",
            text: result.message || "La solicitud se eliminó correctamente."
        });

        await cargarSolicitudes();
    } catch (error) {
        const message = error instanceof Error ? error.message : "No se pudo eliminar la solicitud.";

        Swal.fire({
            icon: "error",
            title: "Error",
            text: message
        });

        console.error(error);
    }
}

async function handleSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);
    const payload = {};

    for (const [key, value] of formData.entries()) {
        if (key === "id") {
            continue;
        }

        payload[key] = normalizeValue(key, value);
    }

    const hoy = new Date();
    hoy.setMinutes(hoy.getMinutes() - hoy.getTimezoneOffset());
    const fechaMinima = hoy.toISOString().split("T")[0];

    if (payload.fecha_preferida && payload.fecha_preferida < fechaMinima) {
        Swal.fire({
            icon: "warning",
            title: "Fecha no válida",
            text: "No puedes seleccionar una fecha anterior a hoy."
        });
        return;
    }

    const saveBtn = document.querySelector("[data-save-btn]");
    const originalText = saveBtn?.textContent || "Guardar solicitud";

    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = "Guardando...";
    }

    try {
        if (state.editingId !== null) {
            const result = await update(state.editingId, payload);

            await Swal.fire({
                icon: "success",
                title: "Actualizada",
                text: result.message || "La solicitud se actualizó correctamente."
            });
        } else {
            const result = await create(payload);

            await Swal.fire({
                icon: "success",
                title: "Guardada",
                text: result.message || "La solicitud se guardó correctamente."
            });
        }

        resetFormDefaults();
        showTable();
        await cargarSolicitudes();
    } catch (error) {
        const message = error instanceof Error ? error.message : "No se pudo guardar la solicitud.";

        Swal.fire({
            icon: "error",
            title: "Error",
            text: message
        });

        console.error(error);
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    }
}


function normalizeValue(field, value) {
    const trimmed = typeof value === "string" ? value.trim() : value;

    if (trimmed === "") {
        return null;
    }

    if (field === "cliente_id" || field === "servicio_id") {
        const number = Number(trimmed);
        return Number.isNaN(number) ? null : number;
    }

    return trimmed;
}
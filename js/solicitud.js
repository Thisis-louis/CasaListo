document.addEventListener("DOMContentLoaded", () => {
    cargarSolicitudes();
});

function cargarSolicitudes() {
    fetch("../php/solicitud.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "getAll" })
    })
    .then(res => res.json())
    .then(json => {
        if (json.status === "success") {
            const tbody = document.getElementById("tbody");

            tbody.innerHTML = json.data.map(s => `
                <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center">#${s.id}</td>
                    <td class="px-4 py-3 text-center">${s.cliente_id ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.servicio_id ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.folio ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.titulo ?? ""}</td>
                    <td class="px-4 py-3">${s.descripcion ?? ""}</td>
                    <td class="px-4 py-3">${s.direccion ?? ""}</td>
                    <td class="px-4 py-3">${s.colonia ?? ""}</td>
                    <td class="px-4 py-3">${s.ciudad ?? ""}</td>
                    <td class="px-4 py-3">${s.estado_region ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.codigo_postal ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.fecha_preferida ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.hora_preferida ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.urgencia ?? ""}</td>
                    <td class="px-4 py-3 text-center">${s.estado ?? ""}</td>
                    <td class="px-4 py-3">
                        <button class="btn btn-sm btn-error" onclick="eliminarSolicitud(${s.id})">
                            Eliminar
                        </button>
                    </td>
                </tr>
            `).join("");
        } else {
            console.error(json.message || "Error al cargar solicitudes");
        }
    })
    .catch(err => console.error("Error:", err));
}

function eliminarSolicitud(id) {
    if (!confirm("¿Deseas eliminar esta solicitud?")) return;

    fetch("../php/solicitudes.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            action: "delete",
            id: id
        })
    })
    .then(res => res.json())
    .then(json => {
        if (json.status === "success") {
            cargarSolicitudes();
        } else {
            alert(json.message || "No se pudo eliminar");
        }
    })
    .catch(err => console.error("Error:", err));
}
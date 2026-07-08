document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.querySelector("#tbody");
    const formQuote = document.getElementById("form-quote");
    const idInput = document.getElementById("id");
    const servicioIdInput = document.getElementById("servicio_id");
    const montoEstimadoInput = document.getElementById("monto_estimado");
    const descripcionInput = document.getElementById("descripcion");
    
    const formTitle = document.getElementById("form-title");
    const btnSubmit = document.getElementById("btn-submit");
    const btnCancel = document.getElementById("btn-cancel");

    getAllQuotes();

    function getAllQuotes() {
        fetch("php/quotes.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "getAll" })
        })
        .then(res => res.json())
        .then(json => {
            tbody.innerHTML = ""; 
            if (json.status === "success") {
                json.data.forEach(quote => {
                    const badgeClass = quote.estado === 'aceptada' ? 'badge-success' : (quote.estado === 'rechazada' ? 'badge-error' : 'badge-warning');
                    tbody.innerHTML += `
                        <tr class="hover">
                            <td class="px-6 py-4 text-sm text-gray-700 font-bold">${quote.id}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">Servicio #${quote.servicio_id}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">${quote.descripcion}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 font-semibold">$${quote.monto_estimado}</td>
                            <td class="px-6 py-4 text-sm text-gray-700"><span class="badge ${badgeClass}">${quote.estado}</span></td>
                            <td class="px-6 py-4 text-sm text-gray-700">${quote.creado_en || ''}</td>
                            <td class="px-6 py-4 text-sm text-center flex justify-center gap-2">
                                <button class="btn btn-warning btn-sm btn-edit" data-quote='${JSON.stringify(quote)}'>Editar</button>
                                <button class="btn btn-error btn-sm btn-delete" data-id="${quote.id}">Eliminar</button>
                            </td>
                        </tr>
                    `;
                });
                asignarEventosAcciones();
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-gray-500">${json.message}</td></tr>`;
            }
        })
        .catch(error => console.error("Error en la petición:", error));
    }

    formQuote.addEventListener("submit", (e) => {
        e.preventDefault();

        const id = idInput.value;
        const payload = {
            action: id ? "update_quote" : "process_quote",
            servicio_id: servicioIdInput.value,
            monto_estimado: montoEstimadoInput.value,
            descripcion: descripcionInput.value
        };

        if (id) payload.id = id;

        fetch("php/quotes.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(json => {
            if (json.status === "success") {
                alert(json.message);
                resetForm();
                getAllQuotes();
            } else {
                alert("Error: " + json.message);
            }
        });
    });

    function asignarEventosAcciones() {
        document.querySelectorAll(".btn-edit").forEach(btn => {
            btn.addEventListener("click", (e) => {
                const quoteData = JSON.parse(e.target.getAttribute("data-quote"));
                
                idInput.value = quoteData.id;
                servicioIdInput.value = quoteData.servicio_id;
                montoEstimadoInput.value = quoteData.monto_estimado;
                descripcionInput.value = quoteData.descripcion;

                formTitle.textContent = "✏️ Editar Cotización #" + quoteData.id;
                btnSubmit.textContent = "Actualizar Cotización";
                btnSubmit.className = "btn btn-warning";
                btnCancel.classList.remove("hidden");
            });
        });

        document.querySelectorAll(".btn-delete").forEach(btn => {
            btn.addEventListener("click", (e) => {
                const id = e.target.getAttribute("data-id");
                if (confirm(`¿Seguro que deseas eliminar la cotización #${id}?`)) {
                    fetch("php/quotes.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ action: "delete_quote", id: id })
                    })
                    .then(res => res.json())
                    .then(json => {
                        if (json.status === "success") {
                            alert(json.message);
                            getAllQuotes();
                        } else {
                            alert("Error: " + json.message);
                        }
                    });
                }
            });
        });
    }

    btnCancel.addEventListener("click", resetForm);

    function resetForm() {
        formQuote.reset();
        idInput.value = "";
        formTitle.textContent = "📝 Registrar Nueva Cotización";
        btnSubmit.textContent = "Guardar Cotización";
        btnSubmit.className = "btn btn-primary";
        btnCancel.classList.add("hidden");
    }
});
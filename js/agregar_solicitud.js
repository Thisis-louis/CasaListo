document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formSolicitud");

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const val = (id) => {
            const v = document.getElementById(id).value.trim();
            return v === "" ? null : v;
        };

        const data = {
            action: "create",
            cliente_id: val("cliente_id"),
            servicio_id: val("servicio_id"),
            folio: val("folio"),
            titulo: val("titulo"),
            descripcion: val("descripcion"),
            direccion: val("direccion"),
            colonia: val("colonia"),
            ciudad: val("ciudad"),
            estado_region: val("estado_region"),
            codigo_postal: val("codigo_postal"),
            fecha_preferida: val("fecha_preferida"),
            hora_preferida: val("hora_preferida"),
            urgencia: val("urgencia"),
            estado: val("estado")
        };

        fetch("../php/solicitud.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(res => res.text())
        .then(text => {
            console.log("Respuesta cruda:", text);
            return JSON.parse(text);
        })
        .then(json => {
            if (json.status === "success") {
                window.location.href = "index.html";
            } else {
                alert(json.message || "No se pudo guardar");
            }
        })
        .catch(err => console.error("Error:", err));
    });
});
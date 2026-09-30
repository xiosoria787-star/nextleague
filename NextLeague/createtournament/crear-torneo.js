// =====================================================================
// NEXTLEAGUE - CREAR TORNEO (conectado al backend real)
// =====================================================================

const form = document.getElementById('tournamentForm');
const alertMessage = document.getElementById('alertMessage');
const submitBtn = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');
const btnLoader = document.getElementById('btnLoader');

function showAlert(msg, type) {
    alertMessage.textContent = msg;
    alertMessage.className = `alert ${type}`;
    alertMessage.classList.remove('hidden');
    alertMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setLoading(loading) {
    submitBtn.disabled = loading;
    btnText.classList.toggle('hidden', loading);
    btnLoader.classList.toggle('hidden', !loading);
}

// ── Cargar la lista de juegos/disciplinas para el select ──
async function cargarJuegos() {
    const select = document.getElementById('id_juego');
    try {
        const res = await fetch('../backend/torneos/listar_juegos.php');
        const data = await res.json();

        if (!data.exito || data.juegos.length === 0) {
            select.innerHTML = '<option value="">No hay juegos cargados todavía</option>';
            return;
        }

        select.innerHTML = '<option value="">Seleccioná un juego</option>' +
            data.juegos.map(j => `<option value="${j.id_juego}">${j.nombre} (${j.categoria})</option>`).join('');
    } catch (err) {
        select.innerHTML = '<option value="">Error al cargar juegos</option>';
    }
}

// ── Enviar el formulario al backend ──
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    setLoading(true);

    const formData = new FormData(form);

    try {
        const res = await fetch('../backend/torneos/crear_torneo.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (!data.exito) {
            showAlert('❌ ' + data.mensaje, 'error');
            setLoading(false);
            return;
        }

        showAlert('✅ Torneo creado exitosamente. Redirigiendo...', 'success');
        setTimeout(() => {
            window.location.href = `../torneos/torneo.html?id=${data.id_torneo}`;
        }, 1300);

    } catch (err) {
        showAlert('❌ No se pudo conectar con el servidor.', 'error');
        setLoading(false);
    }
});

// ── Al cargar la página: exigir sesión iniciada y traer los juegos ──
document.addEventListener('DOMContentLoaded', async () => {
    await nextleagueExigirSesion(); // redirige a login si no hay sesión
    cargarJuegos();
});

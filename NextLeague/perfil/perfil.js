// =====================================================================
// NEXTLEAGUE - PERFIL (conectado al backend real)
// =====================================================================

const RUTA_FOTOS = "../backend/uploads/perfiles/";
let usuarioActual = null;

const form         = document.getElementById('perfilForm');
const alertMessage = document.getElementById('alertMessage');
const submitBtn    = document.getElementById('submitBtn');
const btnText      = document.getElementById('btnText');
const btnLoader    = document.getElementById('btnLoader');
const fotoPreview  = document.getElementById('fotoPreview');
const inputFoto    = document.getElementById('inputFoto');
const btnEliminarFoto = document.getElementById('btnEliminarFoto');

function showAlert(msg, type) {
    alertMessage.textContent = msg;
    alertMessage.className = `alert ${type}`;
    alertMessage.classList.remove('hidden');
}

function setLoading(loading) {
    submitBtn.disabled = loading;
    btnText.classList.toggle('hidden', loading);
    btnLoader.classList.toggle('hidden', !loading);
}

// ── 1) Exigir sesión y cargar los datos del perfil ──
async function cargarPerfil() {
    const sesion = await nextleagueExigirSesion(); // redirige a login si no hay sesión
    if (!sesion.logueado) return;

    usuarioActual = sesion.usuario;

    try {
        const res = await fetch(`../backend/perfil/obtener_perfil.php?id=${usuarioActual.id_usuario}`);
        const data = await res.json();

        if (data.exito) {
            document.getElementById('nombre_usuario').value = data.usuario.nombre_usuario;
            document.getElementById('correo').value = data.usuario.correo;
            document.getElementById('biografia').value = data.usuario.biografia || '';
            if (data.usuario.foto_perfil) {
                fotoPreview.src = RUTA_FOTOS + data.usuario.foto_perfil;
            }
        }
    } catch (err) {
        showAlert('No se pudo conectar con el servidor.', 'error');
    }

    cargarMisTorneos(sesion.usuario.rol);
}

// ── 1.5) Mostrar los torneos relacionados: creados (organizador) o inscripciones (participante) ──
async function cargarMisTorneos(rol) {
    const titulo = document.getElementById('misTorneosTitulo');
    const lista = document.getElementById('misTorneosLista');
    const esOrganizador = rol === 'organizador' || rol === 'administrador';
    const tipo = esOrganizador ? 'creados' : 'inscripciones';
    titulo.textContent = esOrganizador ? 'Torneos que creé' : 'Torneos en los que participo';

    try {
        const res = await fetch(`../backend/torneos/mis_torneos.php?tipo=${tipo}`);
        const data = await res.json();

        if (!data.exito || data.torneos.length === 0) {
            lista.innerHTML = `<p style="opacity:.6;">${esOrganizador ? 'Todavía no creaste ningún torneo.' : 'Todavía no estás inscripto en ningún torneo.'}</p>`;
            return;
        }

        lista.innerHTML = data.torneos.map(t => `
            <a href="../torneos/torneo.html?id=${t.id_torneo}" style="display:block; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); color:#fff; text-decoration:none;">
                <strong>${escapeHtml(t.nombre)}</strong>
                <span style="opacity:.6; font-size:13px;"> · ${escapeHtml(t.juego)} · ${t.estado}</span>
            </a>
        `).join('');
    } catch (err) {
        lista.innerHTML = '<p style="opacity:.6;">No se pudieron cargar los torneos.</p>';
    }
}

// ── 2) Cambiar foto de perfil ──
inputFoto.addEventListener('change', async () => {
    const archivo = inputFoto.files[0];
    if (!archivo) return;

    const formData = new FormData();
    formData.append('id_usuario', usuarioActual.id_usuario);
    formData.append('foto', archivo);

    try {
        const res = await fetch(`../backend/perfil/subir_foto.php`, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.exito) {
            fotoPreview.src = RUTA_FOTOS + data.foto_perfil + '?t=' + Date.now();
            showAlert('Foto de perfil actualizada.', 'success');
        } else {
            showAlert(data.mensaje, 'error');
        }
    } catch (err) {
        showAlert('Error al subir la imagen.', 'error');
    }
});

// ── 3) Eliminar foto de perfil (borra también el archivo del servidor) ──
btnEliminarFoto.addEventListener('click', async () => {
    try {
        const res = await fetch(`../backend/perfil/eliminar_foto.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_usuario: usuarioActual.id_usuario })
        });
        const data = await res.json();

        if (data.exito) {
            fotoPreview.src = '../iconos/profile.png';
            showAlert('Foto eliminada.', 'success');
        } else {
            showAlert(data.mensaje, 'error');
        }
    } catch (err) {
        showAlert('Error al eliminar la imagen.', 'error');
    }
});

// ── 4) Guardar cambios de nombre / correo / contraseña ──
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    setLoading(true);

    const body = {
        id_usuario: usuarioActual.id_usuario,
        nombre_usuario: document.getElementById('nombre_usuario').value.trim(),
        correo: document.getElementById('correo').value.trim(),
        biografia: document.getElementById('biografia').value.trim(),
        password_nueva: document.getElementById('password_nueva').value
    };

    try {
        const res = await fetch(`../backend/perfil/editar_perfil.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();

        showAlert(data.mensaje, data.exito ? 'success' : 'error');
        document.getElementById('password_nueva').value = '';
    } catch (err) {
        showAlert('No se pudo guardar el perfil.', 'error');
    } finally {
        setLoading(false);
    }
});

cargarPerfil();

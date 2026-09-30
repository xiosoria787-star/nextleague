// =====================================================================
// NEXTLEAGUE - PANEL DE ADMINISTRACIÓN
// =====================================================================

const alertMessage = document.getElementById('alertMessage');

function showAlert(msg, type) {
    alertMessage.textContent = msg;
    alertMessage.className = `alert ${type}`;
    alertMessage.classList.remove('hidden');
}

// ── Control de acceso: si no sos administrador, no llegas al panel ──
// Ojo: esto es solo para la experiencia del usuario (evita que vea una
// pantalla rota). La seguridad real está en cada endpoint de backend/admin,
// que vuelve a chequear el rol con requerir_rol(["administrador"]) antes
// de devolver cualquier dato.
async function verificarAcceso() {
    const sesion = await nextleagueObtenerSesion();
    if (!sesion.logueado || sesion.usuario.rol !== 'administrador') {
        window.location.href = '../home/index.html';
        return false;
    }
    return true;
}

// ── Pestañas ──
document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(b => b.classList.remove('activo'));
        document.querySelectorAll('.panel-tab').forEach(p => p.classList.add('hidden'));
        btn.classList.add('activo');
        document.getElementById('panel' + capitalizar(btn.dataset.tab)).classList.remove('hidden');
    });
});
function capitalizar(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

// ── USUARIOS ──
async function cargarUsuarios() {
    const contenedor = document.getElementById('listaUsuarios');
    try {
        const res = await fetch('../backend/admin/listar_usuarios.php');
        const data = await res.json();
        if (!data.exito) { contenedor.innerHTML = `<p class="estado-vacio">${data.mensaje}</p>`; return; }

        contenedor.innerHTML = `
            <table class="admin-tabla">
                <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Torneos creados</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    ${data.usuarios.map(u => `
                        <tr>
                            <td>${escapeHtml(u.nombre_usuario)}</td>
                            <td>${escapeHtml(u.correo)}</td>
                            <td>
                                <select class="selector-rol" data-id="${u.id_usuario}">
                                    ${['administrador','organizador','participante','publico'].map(r =>
                                        `<option value="${r}" ${r === u.rol ? 'selected' : ''}>${r}</option>`).join('')}
                                </select>
                            </td>
                            <td>${u.torneos_creados}</td>
                            <td>${u.activo ? 'Activo' : '<span class="badge-inactivo">Inactivo</span>'}</td>
                            <td><button class="btn-tabla peligro" data-toggle="${u.id_usuario}">${u.activo ? 'Desactivar' : 'Reactivar'}</button></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        contenedor.querySelectorAll('.selector-rol').forEach(select => {
            select.addEventListener('change', () => cambiarRol(select.dataset.id, select.value));
        });
        contenedor.querySelectorAll('[data-toggle]').forEach(btn => {
            btn.addEventListener('click', () => cambiarEstadoUsuario(btn.dataset.toggle));
        });
    } catch (err) {
        contenedor.innerHTML = '<p class="estado-vacio">No se pudo conectar con el servidor.</p>';
    }
}

async function cambiarRol(idUsuario, rolNuevo) {
    try {
        const res = await fetch('../backend/admin/cambiar_rol.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_usuario: idUsuario, rol_nuevo: rolNuevo })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
        if (!data.exito) cargarUsuarios(); // revierte el select visualmente si el backend lo rechazó
    } catch (err) {
        showAlert('No se pudo cambiar el rol.', 'error');
    }
}

async function cambiarEstadoUsuario(idUsuario) {
    try {
        const res = await fetch('../backend/admin/cambiar_estado_usuario.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_usuario: idUsuario })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
        cargarUsuarios();
    } catch (err) {
        showAlert('No se pudo actualizar el usuario.', 'error');
    }
}

// ── TORNEOS ──
async function cargarTorneosAdmin() {
    const contenedor = document.getElementById('listaTorneosAdmin');
    try {
        const res = await fetch('../backend/admin/listar_torneos_admin.php');
        const data = await res.json();
        if (!data.exito || data.torneos.length === 0) {
            contenedor.innerHTML = '<p class="estado-vacio">No hay torneos creados todavía.</p>';
            return;
        }

        contenedor.innerHTML = `
            <table class="admin-tabla">
                <thead><tr><th>Torneo</th><th>Juego</th><th>Organizador</th><th>Estado</th><th>Equipos</th><th></th></tr></thead>
                <tbody>
                    ${data.torneos.map(t => `
                        <tr>
                            <td><a href="../torneos/torneo.html?id=${t.id_torneo}" style="color:#c7b3ff;">${escapeHtml(t.nombre)}</a></td>
                            <td>${escapeHtml(t.juego)}</td>
                            <td>${escapeHtml(t.organizador)}</td>
                            <td>${t.estado}</td>
                            <td>${t.equipos_inscriptos}</td>
                            <td><button class="btn-tabla peligro" data-borrar="${t.id_torneo}">Eliminar</button></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        contenedor.querySelectorAll('[data-borrar]').forEach(btn => {
            btn.addEventListener('click', () => eliminarTorneoAdmin(btn.dataset.borrar));
        });
    } catch (err) {
        contenedor.innerHTML = '<p class="estado-vacio">No se pudo conectar con el servidor.</p>';
    }
}

async function eliminarTorneoAdmin(idTorneo) {
    if (!confirm('¿Seguro que querés eliminar este torneo? Esta acción no se puede deshacer.')) return;
    try {
        const res = await fetch('../backend/torneos/eliminar_torneo.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_torneo: idTorneo })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
        cargarTorneosAdmin();
    } catch (err) {
        showAlert('No se pudo eliminar el torneo.', 'error');
    }
}

// ── AUDITORÍA ──
async function cargarAuditoria() {
    const contenedor = document.getElementById('listaAuditoria');
    try {
        const res = await fetch('../backend/admin/listar_auditoria.php');
        const data = await res.json();
        if (!data.exito || data.registros.length === 0) {
            contenedor.innerHTML = '<p class="estado-vacio">Todavía no hay actividad registrada.</p>';
            return;
        }

        contenedor.innerHTML = `
            <table class="admin-tabla">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Detalle</th></tr></thead>
                <tbody>
                    ${data.registros.map(r => `
                        <tr>
                            <td>${r.fecha}</td>
                            <td>${r.nombre_usuario ? escapeHtml(r.nombre_usuario) : '—'}</td>
                            <td>${escapeHtml(r.accion)}</td>
                            <td>${escapeHtml(r.entidad)}</td>
                            <td>${escapeHtml(r.detalle || '')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } catch (err) {
        contenedor.innerHTML = '<p class="estado-vacio">No se pudo conectar con el servidor.</p>';
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    if (!(await verificarAcceso())) return;
    cargarUsuarios();
    cargarTorneosAdmin();
    cargarAuditoria();
});

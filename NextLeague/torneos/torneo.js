// =====================================================================
// NEXTLEAGUE - DETALLE DE TORNEO
// =====================================================================

const params = new URLSearchParams(window.location.search);
const idTorneo = parseInt(params.get('id'), 10);

let torneoActual = null;
let sesionActual = null;

const elCargando = document.getElementById('cargando');
const elContenido = document.getElementById('contenidoTorneo');
const alertMessage = document.getElementById('alertMessage');

// escapeHtml() la provee shared/auth-ui.js (se carga antes de este script).

function showAlert(msg, type) {
    alertMessage.textContent = msg;
    alertMessage.className = `alert ${type}`;
    alertMessage.classList.remove('hidden');
    alertMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function formatearFecha(fechaISO) {
    if (!fechaISO) return '';
    const [anio, mes, dia] = fechaISO.split('-');
    return `${dia}/${mes}/${anio}`;
}

function formatearMoneda(monto, moneda) {
    const simbolo = moneda === 'USD' ? 'US$' : '$';
    return `${simbolo} ${Number(monto).toLocaleString('es-UY')}`;
}

const NOMBRE_FORMATO = { liga: 'Liga', eliminacion_directa: 'Eliminación directa', sistema_suizo: 'Sistema suizo' };
const NOMBRE_ESTADO = { pendiente: 'Inscripciones abiertas', en_curso: 'En curso', finalizado: 'Finalizado', cancelado: 'Cancelado' };

if (!idTorneo) {
    elCargando.textContent = 'Falta el id del torneo en la URL.';
} else {
    iniciar();
}

async function iniciar() {
    sesionActual = await nextleagueObtenerSesion();
    await cargarTodo();
}

async function cargarTodo() {
    await cargarTorneo();
    if (!torneoActual) return;

    await Promise.all([
        cargarEquipos(),
        cargarEnfrentamientos(),
        torneoActual.formato === 'eliminacion_directa' ? cargarLlaves() : cargarPosiciones()
    ]);

    elCargando.classList.add('hidden');
    elContenido.classList.remove('hidden');
}

// ── TORNEO ──
async function cargarTorneo() {
    try {
        const res = await fetch(`../backend/torneos/obtener_torneo.php?id=${idTorneo}`);
        const data = await res.json();
        if (!data.exito) {
            elCargando.textContent = data.mensaje || 'Torneo no encontrado.';
            return;
        }
        torneoActual = data.torneo;
        pintarEncabezado();
        pintarPanelOrganizador();
        pintarPanelInscripcion();
    } catch (err) {
        elCargando.textContent = 'No se pudo conectar con el servidor.';
    }
}

function pintarEncabezado() {
    const t = torneoActual;
    document.getElementById('torneoNombre').textContent = t.nombre;
    document.getElementById('torneoEstado').textContent = NOMBRE_ESTADO[t.estado] || t.estado;
    document.getElementById('torneoMeta').textContent =
        `${t.juego} · ${NOMBRE_FORMATO[t.formato] || t.formato} · ${formatearFecha(t.fecha_inicio)} - ${formatearFecha(t.fecha_fin)} · Premio ${formatearMoneda(t.premio, t.moneda)} · Organiza: ${t.organizador}`;
    document.getElementById('torneoDescripcion').textContent = t.descripcion || '';
    if (t.logo) document.getElementById('torneoLogo').src = `../backend/uploads/torneos/logos/${t.logo}`;
    const linkReglas = document.getElementById('torneoReglas');
    if (t.reglas_pdf) {
        linkReglas.href = `../backend/uploads/torneos/reglas/${t.reglas_pdf}`;
        linkReglas.classList.remove('hidden');
    }
}

function esOrganizadorOAdmin() {
    if (!sesionActual?.logueado) return false;
    const esAdmin = sesionActual.usuario.rol === 'administrador';
    return esAdmin || parseInt(sesionActual.usuario.id_usuario, 10) === parseInt(torneoActual.id_organizador, 10);
}

function pintarPanelOrganizador() {
    const panel = document.getElementById('panelOrganizador');
    if (!esOrganizadorOAdmin() || torneoActual.estado !== 'pendiente') {
        panel.classList.add('hidden');
        return;
    }
    panel.classList.remove('hidden');
}

function pintarPanelInscripcion() {
    const panel = document.getElementById('panelInscripcion');
    // El organizador de ESTE torneo no puede anotarse a jugarlo (lo tiene
    // que administrar), pero sí puede participar en torneos ajenos.
    const puedeInscribirse = torneoActual.estado === 'pendiente'
        && sesionActual?.logueado
        && !esOrganizadorOAdmin();

    if (!puedeInscribirse) {
        panel.classList.add('hidden');
        return;
    }
    panel.classList.remove('hidden');

    const esIndividual = torneoActual.categoria === 'mental';
    document.getElementById('inscripcionEquipos').classList.toggle('hidden', esIndividual);
    document.getElementById('inscripcionIndividual').classList.toggle('hidden', !esIndividual);
}

document.getElementById('btnGenerarCalendario').addEventListener('click', async () => {
    try {
        const res = await fetch('../backend/enfrentamientos/generar_enfrentamientos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_torneo: idTorneo })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
        if (data.exito) await cargarTodo();
    } catch (err) {
        showAlert('No se pudo generar el calendario.', 'error');
    }
});

document.getElementById('btnInscribirme').addEventListener('click', async () => {
    try {
        const res = await fetch('../backend/inscripciones/inscribirse.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_torneo: idTorneo })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
    } catch (err) {
        showAlert('No se pudo completar la inscripción.', 'error');
    }
});

document.getElementById('formCrearEquipo').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nombre = document.getElementById('nombreEquipo').value.trim();
    try {
        const res = await fetch('../backend/equipos/crear_equipo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_torneo: idTorneo, nombre })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
        if (data.exito) {
            document.getElementById('nombreEquipo').value = '';
            await cargarEquipos();
        }
    } catch (err) {
        showAlert('No se pudo crear el equipo.', 'error');
    }
});

// ── EQUIPOS ──
async function cargarEquipos() {
    const contenedor = document.getElementById('listaEquipos');
    try {
        const res = await fetch(`../backend/equipos/listar_equipos.php?id_torneo=${idTorneo}`);
        const data = await res.json();

        if (!data.exito || data.equipos.length === 0) {
            contenedor.innerHTML = '<p style="opacity:.6;">Todavía no hay equipos anotados.</p>';
            return;
        }

        const miIdUsuario = sesionActual?.logueado ? parseInt(sesionActual.usuario.id_usuario, 10) : null;

        // Puede solicitar unirse cualquiera logueado, menos el organizador
        // de ESTE torneo (a otros torneos sí puede anotarse sin problema).
        const puedeSolicitar = torneoActual.estado === 'pendiente'
            && sesionActual?.logueado
            && !esOrganizadorOAdmin()
            && torneoActual.categoria !== 'mental';

        contenedor.innerHTML = data.equipos.map(eq => {
            const soyCapitan = miIdUsuario && parseInt(eq.id_capitan, 10) === miIdUsuario;
            return `
                <div class="chip-equipo">
                    <span>${escapeHtml(eq.nombre)}</span>
                    <span style="opacity:.5;">(${eq.cantidad_miembros} ${eq.cantidad_miembros == 1 ? 'jugador' : 'jugadores'}${eq.capitan ? ' · cap. ' + escapeHtml(eq.capitan) : ''})</span>
                    ${puedeSolicitar && !soyCapitan ? `<button data-id="${eq.id_equipo}" class="btn-unirse">Solicitar unirme</button>` : ''}
                    ${soyCapitan ? `<button data-id="${eq.id_equipo}" class="btn-ver-solicitudes">Ver solicitudes</button>` : ''}
                </div>
                ${soyCapitan ? `<div id="solicitudes-${eq.id_equipo}" class="panel-solicitudes hidden"></div>` : ''}
            `;
        }).join('');

        contenedor.querySelectorAll('.btn-unirse').forEach(btn => {
            btn.addEventListener('click', () => unirseEquipo(btn.dataset.id));
        });
        contenedor.querySelectorAll('.btn-ver-solicitudes').forEach(btn => {
            btn.addEventListener('click', () => toggleSolicitudes(btn.dataset.id));
        });
    } catch (err) {
        contenedor.innerHTML = '<p style="opacity:.6;">No se pudieron cargar los equipos.</p>';
    }
}

async function unirseEquipo(idEquipo) {
    try {
        const res = await fetch('../backend/equipos/unirse_equipo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_equipo: idEquipo })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
    } catch (err) {
        showAlert('No se pudo enviar la solicitud.', 'error');
    }
}

// ── Panel de solicitudes pendientes (solo lo ve el capitán del equipo) ──
async function toggleSolicitudes(idEquipo) {
    const panel = document.getElementById(`solicitudes-${idEquipo}`);
    if (!panel.classList.contains('hidden')) {
        panel.classList.add('hidden');
        return;
    }

    panel.classList.remove('hidden');
    panel.innerHTML = '<p style="opacity:.6;">Cargando solicitudes...</p>';

    try {
        const res = await fetch(`../backend/equipos/listar_solicitudes.php?id_equipo=${idEquipo}`);
        const data = await res.json();

        if (!data.exito) { panel.innerHTML = `<p style="opacity:.6;">${data.mensaje}</p>`; return; }
        if (data.solicitudes.length === 0) { panel.innerHTML = '<p style="opacity:.6;">No hay solicitudes pendientes.</p>'; return; }

        panel.innerHTML = data.solicitudes.map(s => `
            <div class="fila-solicitud">
                <span>${escapeHtml(s.nombre_usuario)}</span>
                <div>
                    <button class="btn-secundario" data-aceptar="${s.id_solicitud}">Aceptar</button>
                    <button class="btn-secundario" data-rechazar="${s.id_solicitud}">Rechazar</button>
                </div>
            </div>
        `).join('');

        panel.querySelectorAll('[data-aceptar]').forEach(b => b.addEventListener('click', () => responderSolicitud(b.dataset.aceptar, 'aceptar', idEquipo)));
        panel.querySelectorAll('[data-rechazar]').forEach(b => b.addEventListener('click', () => responderSolicitud(b.dataset.rechazar, 'rechazar', idEquipo)));
    } catch (err) {
        panel.innerHTML = '<p style="opacity:.6;">No se pudieron cargar las solicitudes.</p>';
    }
}

async function responderSolicitud(idSolicitud, accion, idEquipo) {
    try {
        const res = await fetch('../backend/equipos/responder_solicitud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_solicitud: idSolicitud, accion })
        });
        const data = await res.json();
        showAlert(data.mensaje, data.exito ? 'success' : 'error');
        await cargarEquipos();
        if (data.exito) toggleSolicitudes(idEquipo); // vuelve a abrir el panel ya actualizado
    } catch (err) {
        showAlert('No se pudo responder la solicitud.', 'error');
    }
}

// ── TABLA DE POSICIONES ──
async function cargarPosiciones() {
    if (torneoActual.formato === 'eliminacion_directa') return;
    const panel = document.getElementById('panelPosiciones');
    const cuerpo = document.getElementById('cuerpoPosiciones');
    try {
        const res = await fetch(`../backend/posiciones/listar_posiciones.php?id_torneo=${idTorneo}`);
        const data = await res.json();
        if (!data.exito || data.posiciones.length === 0) {
            panel.classList.add('hidden');
            return;
        }
        panel.classList.remove('hidden');
        cuerpo.innerHTML = data.posiciones.map(p => `
            <tr>
                <td>${escapeHtml(p.equipo)}</td>
                <td>${p.pj}</td><td>${p.pg}</td><td>${p.pe}</td><td>${p.pp}</td>
                <td>${p.gf}</td><td>${p.gc}</td><td><strong>${p.puntos}</strong></td>
            </tr>
        `).join('');
    } catch (err) {
        panel.classList.add('hidden');
    }
}

// ── LLAVES ──
async function cargarLlaves() {
    const panel = document.getElementById('panelLlaves');
    const contenedor = document.getElementById('listaLlaves');
    try {
        const res = await fetch(`../backend/llaves/listar_llaves.php?id_torneo=${idTorneo}`);
        const data = await res.json();
        if (!data.exito || data.llaves.length === 0) {
            panel.classList.add('hidden');
            return;
        }
        panel.classList.remove('hidden');

        const porRonda = {};
        data.llaves.forEach(l => {
            (porRonda[l.ronda] = porRonda[l.ronda] || []).push(l);
        });

        contenedor.innerHTML = Object.keys(porRonda).sort((a, b) => a - b).map(ronda => `
            <div>
                <div class="ronda-titulo">Ronda ${ronda}</div>
                ${porRonda[ronda].map(p => `
                    <div class="fila-partido">
                        <span class="equipos">
                            ${p.equipo_local ? escapeHtml(p.equipo_local) : 'Por definir'}
                            ${p.id_ganador == p.id_equipo_local ? ' 🏆' : ''}
                            &nbsp;vs&nbsp;
                            ${p.equipo_visitante ? escapeHtml(p.equipo_visitante) : 'Por definir'}
                        </span>
                        <span class="resultado">${p.resultado_local ?? '-'} : ${p.resultado_visitante ?? '-'}</span>
                    </div>
                `).join('')}
            </div>
        `).join('');
    } catch (err) {
        panel.classList.add('hidden');
    }
}

// ── CALENDARIO / ENFRENTAMIENTOS (con carga de resultado si sos el organizador) ──
async function cargarEnfrentamientos() {
    const contenedor = document.getElementById('listaEnfrentamientos');
    try {
        const res = await fetch(`../backend/enfrentamientos/listar_enfrentamientos.php?id_torneo=${idTorneo}`);
        const data = await res.json();

        if (!data.exito || data.enfrentamientos.length === 0) {
            contenedor.innerHTML = '<p style="opacity:.6;">Todavía no se generó el calendario.</p>';
            return;
        }

        const puedeCargarResultado = esOrganizadorOAdmin();

        contenedor.innerHTML = data.enfrentamientos.map(p => {
            const ambosDefinidos = p.equipo_local && p.equipo_visitante;
            const finalizado = p.estado === 'finalizado';

            let accion = '';
            if (puedeCargarResultado && ambosDefinidos) {
                // Si ya tiene resultado, se muestra el formulario para corregirlo
                // (precargado con los valores actuales) en vez de ocultarlo.
                accion = `
                    <form class="form-resultado" data-id="${p.id_enfrentamiento}">
                        <input type="number" min="0" name="local" value="${finalizado ? p.resultado_local : ''}" placeholder="0" required>
                        <span>-</span>
                        <input type="number" min="0" name="visitante" value="${finalizado ? p.resultado_visitante : ''}" placeholder="0" required>
                        <button type="submit">${finalizado ? 'Corregir' : 'Guardar'}</button>
                    </form>
                `;
            }

            return `
                <div class="fila-partido">
                    <span class="equipos">Ronda ${p.ronda}: ${p.equipo_local ? escapeHtml(p.equipo_local) : 'Por definir'} vs ${p.equipo_visitante ? escapeHtml(p.equipo_visitante) : 'Por definir'}</span>
                    ${finalizado && !puedeCargarResultado ? `<span class="resultado">${p.resultado_local} : ${p.resultado_visitante}</span>` : ''}
                    ${!finalizado ? `<span class="estado-tag">${p.estado}</span>` : ''}
                    ${accion}
                </div>
            `;
        }).join('');

        contenedor.querySelectorAll('.form-resultado').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const idEnf = form.dataset.id;
                const local = form.local.value;
                const visitante = form.visitante.value;
                try {
                    const res = await fetch('../backend/enfrentamientos/registrar_resultado.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_enfrentamiento: idEnf, resultado_local: local, resultado_visitante: visitante })
                    });
                    const data = await res.json();
                    showAlert(data.mensaje, data.exito ? 'success' : 'error');
                    if (data.exito) await cargarTodo();
                } catch (err) {
                    showAlert('No se pudo guardar el resultado.', 'error');
                }
            });
        });
    } catch (err) {
        contenedor.innerHTML = '<p style="opacity:.6;">No se pudo cargar el calendario.</p>';
    }
}

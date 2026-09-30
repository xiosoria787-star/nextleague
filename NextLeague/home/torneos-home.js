// =====================================================================
// NEXTLEAGUE - HOME: listado de torneos real (reemplaza los de ejemplo)
// =====================================================================

async function nextleagueCargarTorneosHome() {
    const contenedor = document.getElementById('listaTorneos');
    if (!contenedor) return;

    try {
        const res = await fetch('../backend/torneos/listar_torneos.php');
        const data = await res.json();

        if (!data.exito || data.torneos.length === 0) {
            contenedor.innerHTML = '<p style="opacity:.7;">Todavía no hay torneos creados. ¡Creá el primero!</p>';
            return;
        }

        contenedor.innerHTML = data.torneos.map(t => {
            const cupo = `${t.equipos_inscriptos} / ${t.num_equipos} Equipos`;
            const abierto = t.estado === 'pendiente';
            const estadoTexto = abierto ? 'Inscripciones abiertas'
                              : t.estado === 'en_curso' ? 'En curso'
                              : t.estado === 'finalizado' ? 'Finalizado' : 'Cancelado';
            const imagen = t.logo ? `../backend/uploads/torneos/logos/${t.logo}` : '../iconos/images.jpg';

            return `
                <div class="cardtournment" style="position:relative;">
                    <a href="../torneos/torneo.html?id=${t.id_torneo}" style="text-decoration:none; color:inherit; display:block;">
                        <div class="cardtournment-dateecard">🕘 ${formatearFecha(t.fecha_inicio)}</div>
                        <div class="cardtournment-image-container">
                            <img src="${imagen}" alt="${escapeHtml(t.nombre)}" class="cardtournment-image">
                        </div>
                        <div class="cardtournment-text">
                            <h3>${escapeHtml(t.nombre)}</h3>
                            <p class="typeofgame">${escapeHtml(t.juego)} · ${formatearFormato(t.formato)} · ${t.premio > 0 ? formatearMoneda(t.premio, t.moneda) : ''}</p>
                        </div>
                        <p>${cupo}</p>
                    </a>
                    <div class="tournament-status ${abierto ? 'open' : ''}">
                        ${abierto
                            ? `<button class="btn-link btn-inscribirse-card" data-id="${t.id_torneo}">Inscribirse</button>`
                            : `<span class="btn-link">${estadoTexto}</span>`}
                    </div>
                </div>
            `;
        }).join('');

        contenedor.querySelectorAll('.btn-inscribirse-card').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                // La inscripción (individual o crear/unirse a equipo) se resuelve
                // en la página del torneo, donde ya sabemos si es por equipos o individual.
                window.location.href = `../torneos/torneo.html?id=${btn.dataset.id}`;
            });
        });

    } catch (err) {
        contenedor.innerHTML = '<p style="opacity:.7;">No se pudo conectar con el servidor.</p>';
    }
}

// ── Tarjeta destacada del hero: el torneo activo con mayor premio ──
async function nextleagueCargarDestacado() {
    const card = document.getElementById('activeTournamentCard');
    if (!card) return;

    try {
        const res = await fetch('../backend/torneos/listar_torneos.php');
        const data = await res.json();
        if (!data.exito) return;

        const activos = data.torneos.filter(t => t.estado === 'pendiente' || t.estado === 'en_curso');
        if (activos.length === 0) {
            card.style.display = 'none';
            return;
        }

        const destacado = activos.reduce((mejor, t) => (t.premio > mejor.premio ? t : mejor), activos[0]);
        const porcentaje = Math.min(100, Math.round((destacado.equipos_inscriptos / destacado.num_equipos) * 100));

        document.getElementById('activeTournamentName').textContent = destacado.nombre;
        document.getElementById('prizePool').textContent = formatearMoneda(destacado.premio, destacado.moneda);
        document.getElementById('registeredTeams').textContent = `${destacado.equipos_inscriptos} / ${destacado.num_equipos} equipos`;
        const barra = card.querySelector('.progress-fill');
        if (barra) barra.style.width = porcentaje + '%';
        card.querySelector('.progress-bar')?.setAttribute('aria-valuenow', porcentaje);

        card.onclick = () => window.location.href = `../torneos/torneo.html?id=${destacado.id_torneo}`;
        card.style.cursor = 'pointer';
    } catch (err) { /* si falla, se deja el contenido de ejemplo */ }
}

function formatearFecha(fechaISO) {
    if (!fechaISO) return '';
    const [anio, mes, dia] = fechaISO.split('-');
    return `${dia}/${mes}/${anio}`;
}

function formatearFormato(formato) {
    return { liga: 'Liga', eliminacion_directa: 'Eliminación directa', sistema_suizo: 'Sistema suizo' }[formato] || formato;
}

function formatearMoneda(monto, moneda) {
    const simbolo = moneda === 'USD' ? 'US$' : '$';
    return `${simbolo} ${Number(monto).toLocaleString('es-UY')}`;
}

document.getElementById('exploreTournamentsButton')?.addEventListener('click', () => {
    window.location.href = '../torneos/explorar.html';
});

// ── Calendario y resultados recientes del home, con filtro por deporte ──
async function nextleagueCargarFiltroDeporteHome() {
    const select = document.getElementById('filtroDeporteHome');
    if (!select) return;
    try {
        const res = await fetch('../backend/torneos/listar_juegos.php');
        const data = await res.json();
        if (data.exito) {
            select.innerHTML = '<option value="">Todos</option>' +
                data.juegos.map(j => `<option value="${j.id_juego}">${escapeHtml(j.nombre)}</option>`).join('');
        }
    } catch (err) { /* si falla, se queda solo la opción "Todos" */ }
    select.addEventListener('change', () => {
        nextleagueCargarCalendarioHome();
        nextleagueCargarResultadosHome();
    });
}

async function nextleagueCargarCalendarioHome() {
    const contenedor = document.getElementById('calendarioProximos');
    if (!contenedor) return;
    const idJuego = document.getElementById('filtroDeporteHome')?.value || '';

    try {
        const url = '../backend/enfrentamientos/listar_enfrentamientos.php?proximos=1' + (idJuego ? `&id_juego=${idJuego}` : '');
        const res = await fetch(url);
        const data = await res.json();

        if (!data.exito || data.enfrentamientos.length === 0) {
            contenedor.innerHTML = '<p class="estado-vacio">No hay próximos partidos programados por ahora.</p>';
            return;
        }

        contenedor.innerHTML = data.enfrentamientos.map(p => `
            <a href="../torneos/torneo.html?id=${p.id_torneo}" class="calendary-card">
                <div class="fila">
                    <div class="first-team">${escapeHtml(p.equipo_local)}</div>
                    <div class="versus">VS</div>
                    <div class="scnd-team">${escapeHtml(p.equipo_visitante)}</div>
                </div>
                <div class="description">
                    <div class="league">${escapeHtml(p.torneo)}</div>
                    <div class="stadistics">${escapeHtml(p.juego)}</div>
                </div>
            </a>
        `).join('');
    } catch (err) {
        contenedor.innerHTML = '<p class="estado-vacio">No se pudo cargar el calendario.</p>';
    }
}

async function nextleagueCargarResultadosHome() {
    const contenedor = document.getElementById('resultadosRecientes');
    if (!contenedor) return;
    const idJuego = document.getElementById('filtroDeporteHome')?.value || '';

    try {
        const url = '../backend/enfrentamientos/listar_enfrentamientos.php?recientes=1' + (idJuego ? `&id_juego=${idJuego}` : '');
        const res = await fetch(url);
        const data = await res.json();

        if (!data.exito || data.enfrentamientos.length === 0) {
            contenedor.innerHTML = '<p class="estado-vacio">Todavía no hay resultados registrados.</p>';
            return;
        }

        contenedor.innerHTML = data.enfrentamientos.map(p => `
            <a href="../torneos/torneo.html?id=${p.id_torneo}" class="fila-resultado-home">
                <span class="equipos">${escapeHtml(p.equipo_local)} <strong>${p.resultado_local} - ${p.resultado_visitante}</strong> ${escapeHtml(p.equipo_visitante)}</span>
                <span class="torneo-tag">${escapeHtml(p.torneo)}</span>
            </a>
        `).join('');
    } catch (err) {
        contenedor.innerHTML = '<p class="estado-vacio">No se pudieron cargar los resultados.</p>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    nextleagueCargarTorneosHome();
    nextleagueCargarDestacado();
    nextleagueCargarFiltroDeporteHome();
    nextleagueCargarCalendarioHome();
    nextleagueCargarResultadosHome();
});

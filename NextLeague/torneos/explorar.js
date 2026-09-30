// =====================================================================
// NEXTLEAGUE - EXPLORAR TORNEOS
// =====================================================================

let tabActual = 'todos';
let sesionActual = null;

const contenedor = document.getElementById('listaTorneos');
const filtroJuego = document.getElementById('filtroJuego');
const filtroEstado = document.getElementById('filtroEstado');

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
const NOMBRE_ESTADO = { pendiente: 'Inscripciones abiertas', en_curso: 'En curso', finalizado: 'Finalizado', cancelado: 'Cancelado' };

async function cargarJuegosFiltro() {
    try {
        const res = await fetch('../backend/torneos/listar_juegos.php');
        const data = await res.json();
        if (data.exito) {
            filtroJuego.innerHTML = '<option value="">Todos los deportes/juegos</option>' +
                data.juegos.map(j => `<option value="${j.id_juego}">${j.nombre}</option>`).join('');
        }
    } catch (err) { /* si falla, se queda solo la opción "todos" */ }
}

async function cargarTorneos() {
    contenedor.innerHTML = '<p style="opacity:.7;">Cargando torneos...</p>';

    let url;
    if (tabActual === 'todos') {
        url = '../backend/torneos/listar_torneos.php';
        if (filtroEstado.value) url += `?estado=${filtroEstado.value}`;
    } else {
        url = `../backend/torneos/mis_torneos.php?tipo=${tabActual}`;
    }

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (!data.exito) {
            contenedor.innerHTML = `<p style="opacity:.7;">${data.mensaje || 'No se pudieron cargar los torneos.'}</p>`;
            return;
        }

        let torneos = data.torneos;
        if (filtroJuego.value) {
            torneos = torneos.filter(t => String(t.id_juego) === filtroJuego.value);
        }

        if (torneos.length === 0) {
            contenedor.innerHTML = '<p style="opacity:.7;">No hay torneos para mostrar acá.</p>';
            return;
        }

        contenedor.innerHTML = torneos.map(t => {
            const imagen = t.logo ? `../backend/uploads/torneos/logos/${t.logo}` : '../iconos/images.jpg';
            const abierto = t.estado === 'pendiente';
            return `
                <a href="torneo.html?id=${t.id_torneo}" class="card-torneo">
                    <img src="${imagen}" alt="${escapeHtml(t.nombre)}">
                    <div class="info">
                        <h3>${escapeHtml(t.nombre)}</h3>
                        <p>${escapeHtml(t.juego)} · ${formatearFormato(t.formato)}</p>
                        <p>${formatearFecha(t.fecha_inicio)} - ${formatearFecha(t.fecha_fin)} · ${formatearMoneda(t.premio, t.moneda)}</p>
                        <p>${t.equipos_inscriptos} / ${t.num_equipos} equipos</p>
                        <span class="estado ${abierto ? 'abierto' : ''}">${NOMBRE_ESTADO[t.estado] || t.estado}</span>
                    </div>
                </a>
            `;
        }).join('');
    } catch (err) {
        contenedor.innerHTML = '<p style="opacity:.7;">No se pudo conectar con el servidor.</p>';
    }
}

document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', async () => {
        if ((btn.dataset.tab === 'inscripciones' || btn.dataset.tab === 'creados') && !sesionActual?.logueado) {
            window.location.href = '../home/login.html';
            return;
        }
        document.querySelectorAll('.tab').forEach(b => b.classList.remove('activo'));
        btn.classList.add('activo');
        tabActual = btn.dataset.tab;
        cargarTorneos();
    });
});

filtroJuego.addEventListener('change', cargarTorneos);
filtroEstado.addEventListener('change', cargarTorneos);

document.addEventListener('DOMContentLoaded', async () => {
    sesionActual = await nextleagueObtenerSesion();
    cargarJuegosFiltro();
    cargarTorneos();
});

// =====================================================================
// NEXTLEAGUE - AUTH UI (compartido por todas las páginas)
// Se incluye con: <script src="../shared/auth-ui.js"></script>
// (funciona igual desde home/, register/, createtournament/, perfil/, torneos/
// porque todas esas carpetas están al mismo nivel, un paso abajo de NextLeague/)
// =====================================================================

const NEXTLEAGUE_BACKEND = "../backend";

// ── Trae la sesión actual una sola vez y la reutiliza (memoizada) ──
let _sesionPromesa = null;
function nextleagueObtenerSesion() {
    if (!_sesionPromesa) {
        _sesionPromesa = fetch(`${NEXTLEAGUE_BACKEND}/auth/quien_soy.php`)
            .then(res => res.json())
            .catch(() => ({ exito: false, logueado: false }));
    }
    return _sesionPromesa;
}

// ── Cierra sesión y recarga la página ──
async function nextleagueCerrarSesion() {
    await fetch(`${NEXTLEAGUE_BACKEND}/auth/logout.php`, { method: "POST" });
    window.location.reload();
}

// ── Estilos del menú de usuario (se inyectan una sola vez) ──
(function inyectarEstilos() {
    if (document.getElementById('nextleague-auth-ui-css')) return;
    const style = document.createElement('style');
    style.id = 'nextleague-auth-ui-css';
    style.textContent = `
        .nl-user-menu { display: flex; align-items: center; gap: 10px; position: relative; }
        .nl-user-chip {
            display: flex; align-items: center; gap: 8px;
            background: rgba(141,2,255,0.12); border: 1px solid rgba(141,2,255,0.4);
            padding: 7px 12px; border-radius: 999px; cursor: pointer;
            color: #fff; font-size: 14px; font-family: inherit;
        }
        .nl-user-chip:hover { background: rgba(141,2,255,0.22); }
        .nl-user-avatar { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; background: #2a1a4d; }
        .nl-dropdown {
            position: absolute; top: calc(100% + 8px); right: 0;
            background: #150a2e; border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px; min-width: 180px; padding: 6px;
            display: none; flex-direction: column; z-index: 200;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .nl-dropdown.abierto { display: flex; }
        .nl-dropdown a, .nl-dropdown button {
            padding: 9px 12px; border-radius: 6px; background: none; border: none;
            color: #e6e0ff; text-align: left; font-size: 14px; cursor: pointer; text-decoration: none;
            font-family: inherit;
        }
        .nl-dropdown a:hover, .nl-dropdown button:hover { background: rgba(255,255,255,0.06); }
        .nl-dropdown .nl-cerrar { color: #FFA3A3; }
    `;
    document.head.appendChild(style);
})();

// ── Arma el contenido de #authArea según haya o no sesión ──
function nextleaguePintarAuthArea(datos) {
    const area = document.getElementById('authArea');
    if (!area) return;

    if (!datos.logueado) {
        area.innerHTML = `
            <button class="btn btn-secondary" onclick="window.location.href='${rutaRelativaHome()}login.html'">Iniciar sesión</button>
            <button class="btn btn-outline" onclick="window.location.href='${rutaRelativaHome()}../register/register.html'">Registrarse</button>
        `;
        return;
    }

    const nombre = datos.usuario.nombre_usuario;
    const avatar = datos.usuario.foto_perfil
        ? `${rutaRelativaHome()}../backend/uploads/perfiles/${datos.usuario.foto_perfil}`
        : `${rutaRelativaHome()}../iconos/profile.png`;
    area.innerHTML = `
        <div class="nl-user-menu">
            <button class="nl-user-chip" id="nlUserChipBtn">
                <img src="${avatar}" class="nl-user-avatar" alt="">
                ${nombre}
            </button>
            <div class="nl-dropdown" id="nlDropdown">
                <a href="${rutaRelativaHome()}../perfil/perfil.html">Mi perfil</a>
                ${datos.usuario.rol !== 'participante' ? `<a href="${rutaRelativaHome()}../createtournament/createtournament.html">Crear torneo</a>` : ''}
                ${datos.usuario.rol === 'administrador' ? `<a href="${rutaRelativaHome()}../admin/admin.html">Panel de administración</a>` : ''}
                <button class="nl-cerrar" id="nlCerrarSesionBtn">Cerrar sesión</button>
            </div>
        </div>
    `;

    const chip = document.getElementById('nlUserChipBtn');
    const dropdown = document.getElementById('nlDropdown');
    chip.addEventListener('click', () => dropdown.classList.toggle('abierto'));
    document.addEventListener('click', (e) => {
        if (!chip.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('abierto');
        }
    });
    document.getElementById('nlCerrarSesionBtn').addEventListener('click', nextleagueCerrarSesion);
}

// Todas las páginas que usan este script están un nivel bajo NextLeague/,
// así que la ruta a home/ siempre es "../home/" salvo estando ya en home/.
function rutaRelativaHome() {
    const enHome = window.location.pathname.includes('/home/');
    return enHome ? '' : '../home/';
}

// Escapa texto antes de insertarlo en el HTML (nombres de torneo/equipo/usuario
// vienen de la base de datos y los puso otro usuario; sin esto, un nombre con
// "<img onerror=...>" se ejecutaría como HTML para cualquiera que lo vea).
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

// ── Si la página tiene un elemento con id="requiereSesion", redirige al login
// cuando no hay sesion iniciada (usado en crear torneo, perfil, etc). ──
async function nextleagueExigirSesion() {
    const datos = await nextleagueObtenerSesion();
    if (!datos.logueado) {
        window.location.href = `${rutaRelativaHome()}login.html`;
    }
    return datos;
}

// ── Auto-inicio: pinta el navbar apenas carga cualquier página que lo incluya ──
document.addEventListener('DOMContentLoaded', async () => {
    const datos = await nextleagueObtenerSesion();
    nextleaguePintarAuthArea(datos);
});

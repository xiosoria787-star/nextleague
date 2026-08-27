// ========================
// NEXTLEAGUE - REGISTER JS
// ========================

const form = document.getElementById('registerForm');
const alertMessage = document.getElementById('alertMessage');
const submitBtn = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');
const btnLoader = document.getElementById('btnLoader');

// ── Mostrar/ocultar contraseña ──
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁️';
}

// ── Fuerza de contraseña ──
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const fill = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');

    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { width: '0%',   color: 'transparent', text: '' },
        { width: '25%',  color: '#ff4444',      text: 'Muy débil' },
        { width: '50%',  color: '#ff9900',      text: 'Débil' },
        { width: '75%',  color: '#f0e040',      text: 'Buena' },
        { width: '100%', color: '#00c853',      text: 'Muy fuerte' },
    ];

    const lvl = val.length === 0 ? levels[0] : levels[score];
    fill.style.width = lvl.width;
    fill.style.backgroundColor = lvl.color;
    label.textContent = lvl.text;
    label.style.color = lvl.color;
});

// ── Validación de campos ──
function showError(fieldId, msg) {
    const input = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + 'Error');
    if (input) input.classList.add('input-error');
    if (error) error.textContent = msg;
}

function clearError(fieldId) {
    const input = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + 'Error');
    if (input) { input.classList.remove('input-error'); input.classList.add('input-ok'); }
    if (error) error.textContent = '';
}

function clearAllErrors() {
    ['username', 'email', 'password', 'confirmPassword'].forEach(id => {
        const input = document.getElementById(id);
        const error = document.getElementById(id + 'Error');
        if (input) { input.classList.remove('input-error', 'input-ok'); }
        if (error) error.textContent = '';
    });
    document.getElementById('termsError').textContent = '';
}

function validateForm() {
    let valid = true;
    clearAllErrors();

    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const terms = document.getElementById('terms').checked;

    if (username.length < 3) {
        showError('username', 'El nombre de usuario debe tener al menos 3 caracteres.');
        valid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showError('username', 'Solo se permiten letras, números y guiones bajos.');
        valid = false;
    } else {
        clearError('username');
    }

    if (!email) {
        showError('email', 'El correo es obligatorio.');
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('email', 'Ingresá un correo válido.');
        valid = false;
    } else {
        clearError('email');
    }

    if (password.length < 8) {
        showError('password', 'La contraseña debe tener al menos 8 caracteres.');
        valid = false;
    } else {
        clearError('password');
    }

    if (!confirmPassword) {
        showError('confirmPassword', 'Confirmá tu contraseña.');
        valid = false;
    } else if (password !== confirmPassword) {
        showError('confirmPassword', 'Las contraseñas no coinciden.');
        valid = false;
    } else {
        clearError('confirmPassword');
    }

    if (!terms) {
        document.getElementById('termsError').textContent = 'Debés aceptar los términos para continuar.';
        valid = false;
    }

    return valid;
}

// ── Alerta global ──
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

// ── Submit ──
form.addEventListener('submit', async function (e) {
    e.preventDefault();

    if (!validateForm()) return;

    setLoading(true);

    const data = {
        username: document.getElementById('username').value.trim(),
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value,
    };

    // Simulación de registro (reemplazá con una API real, si llegamos a usar )
    try {
        await new Promise(resolve => setTimeout(resolve, 1500)); // simular request

        // Ejemplo de lo que harías con un backend real:
        // const res = await fetch('/api/register', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify(data)
        // });
        // if (!res.ok) throw new Error('Error al registrar');

        showAlert('✅ ¡Cuenta creada exitosamente! Redirigiendo...', 'success');

        setTimeout(() => {
            window.location.href = '../home /login.html';
        }, 2000);

    } catch (err) {
        showAlert('❌ Ocurrió un error al registrar. Intentá de nuevo.', 'error');
        setLoading(false);
    }
});

// Limpiar errores al escribir
['username', 'email', 'password', 'confirmPassword'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        const input = document.getElementById(id);
        const error = document.getElementById(id + 'Error');
        if (input.classList.contains('input-error') && input.value.trim()) {
            input.classList.remove('input-error');
            if (error) error.textContent = '';
        }
    });
});

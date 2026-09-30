const form = document.getElementById('loginForm');
const alertMessage = document.getElementById('alertMessage');
const submitBtn = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');
const btnLoader = document.getElementById('btnLoader');

// ── Mostrar/ocultar contraseña ──
function togglePassword() {
    const input = document.getElementById('password');
    const btn = document.querySelector('.toggle-password');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁️';
}

// ── Utilidades ──
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

function showError(fieldId, msg) {
    const input = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + 'Error');
    if (input) input.classList.add('input-error');
    if (error) error.textContent = msg;
}

function clearErrors() {
    ['email', 'password'].forEach(id => {
        const input = document.getElementById(id);
        const error = document.getElementById(id + 'Error');
        if (input) input.classList.remove('input-error');
        if (error) error.textContent = '';
    });
    alertMessage.className = 'alert hidden';
}

function validateForm() {
    let valid = true;
    clearErrors();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email) {
        showError('email', 'El correo es obligatorio.');
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('email', 'Ingresá un correo válido.');
        valid = false;
    }

    if (!password) {
        showError('password', 'La contraseña es obligatoria.');
        valid = false;
    }

    return valid;
}

// ── Submit ──
form.addEventListener('submit', async function (e) {
    e.preventDefault();

    if (!validateForm()) return;

    setLoading(true);

    const data = {
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value,
        remember: document.getElementById('rememberMe').checked,
    };

    // ── Llamada real al backend (PHP + MySQL) ──
    try {
        const res = await fetch('../backend/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const resultado = await res.json();

        if (!resultado.exito) {
            showAlert('❌ ' + resultado.mensaje, 'error');
            setLoading(false);
            return;
        }

        showAlert('✅ ¡Sesión iniciada! Redirigiendo...', 'success');

        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1200);

    } catch (err) {
        showAlert('❌ No se pudo conectar con el servidor. Revisá que Apache y MySQL estén prendidos en XAMPP.', 'error');
        setLoading(false);
    }
});

// Limpiar error al escribir
['email', 'password'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        const input = document.getElementById(id);
        const error = document.getElementById(id + 'Error');
        if (input.classList.contains('input-error') && input.value) {
            input.classList.remove('input-error');
            if (error) error.textContent = '';
        }
    });
});

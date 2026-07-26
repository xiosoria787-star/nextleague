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

    try {
        await new Promise(resolve => setTimeout(resolve, 1400)); // simular request

        // Ejemplo con backend real:
        // const res = await fetch('/api/login', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify(data)
        // });
        // if (!res.ok) throw new Error('Credenciales incorrectas');
        // ojala se entineda ujajaja;

        showAlert('✅ ¡Sesión iniciada! Redirigiendo...', 'success');

        setTimeout(() => {
            window.location.href = '../furo home jaja';
        }, 1500);

    } catch (err) {
        showAlert('❌ Email o contraseña incorrectos. Intentá de nuevo.', 'error');
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

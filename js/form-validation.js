// Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateFormInputs(form)) {
                e.preventDefault();
                showToast('Mohon lengkapi semua field yang wajib diisi', 'warning');
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateInput(input);
            });
            
            input.addEventListener('input', function() {
                if (input.classList.contains('error')) {
                    validateInput(input);
                }
            });
        });
    });
});

function validateFormInputs(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateInput(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateInput(input) {
    const value = input.value.trim();
    let isValid = true;
    let errorMessage = '';

    // Required check
    if (input.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Field ini wajib diisi';
    }

    // Email validation
    if (input.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Format email tidak valid';
        }
    }

    // URL validation
    if (input.type === 'url' && value) {
        try {
            new URL(value);
        } catch {
            isValid = false;
            errorMessage = 'Format URL tidak valid';
        }
    }

    // Number validation
    if (input.type === 'number') {
        const min = input.getAttribute('min');
        const max = input.getAttribute('max');
        
        if (min && parseFloat(value) < parseFloat(min)) {
            isValid = false;
            errorMessage = `Nilai minimum adalah ${min}`;
        }
        
        if (max && parseFloat(value) > parseFloat(max)) {
            isValid = false;
            errorMessage = `Nilai maksimum adalah ${max}`;
        }
    }

    // Password confirmation
    if (input.getAttribute('data-confirm')) {
        const confirmInput = document.getElementById(input.getAttribute('data-confirm'));
        if (confirmInput && value !== confirmInput.value) {
            isValid = false;
            errorMessage = 'Password tidak cocok';
        }
    }

    // Min length
    const minLength = input.getAttribute('minlength');
    if (minLength && value.length < parseInt(minLength)) {
        isValid = false;
        errorMessage = `Minimal ${minLength} karakter`;
    }

    // Max length
    const maxLength = input.getAttribute('maxlength');
    if (maxLength && value.length > parseInt(maxLength)) {
        isValid = false;
        errorMessage = `Maksimal ${maxLength} karakter`;
    }

    // Update UI
    const errorEl = input.parentElement.querySelector('.error-message');
    
    if (isValid) {
        input.classList.remove('error');
        if (errorEl) errorEl.remove();
    } else {
        input.classList.add('error');
        if (!errorEl) {
            const error = document.createElement('div');
            error.className = 'error-message';
            error.textContent = errorMessage;
            input.parentElement.appendChild(error);
        } else {
            errorEl.textContent = errorMessage;
        }
    }

    return isValid;
}

// Password visibility toggle
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'password-toggle';
        toggle.innerHTML = '<i class="fas fa-eye"></i>';
        toggle.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666;';
        
        toggle.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                toggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                toggle.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
        
        wrapper.appendChild(toggle);
    });
});

// Add error message styles
if (!document.querySelector('style[data-validation-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-validation-styles', 'true');
    style.textContent = `
        input.error,
        textarea.error,
        select.error {
            border-color: #dc3545 !important;
        }
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }
        .password-toggle:hover {
            color: #D4AF37;
        }
    `;
    document.head.appendChild(style);
}

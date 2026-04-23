// Modal functionality
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    // Hide alerts when closing
    document.querySelectorAll('.alert').forEach(a => a.style.display = 'none');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}

// Toggle between Login and Signup forms
function toggleForm(hideId, showId) {
    document.getElementById(hideId).classList.add('hidden');
    document.getElementById(showId).classList.remove('hidden');
    // Hide alerts when toggling
    document.querySelectorAll('.alert').forEach(a => a.style.display = 'none');
}

// Generic function to handle form submission via AJAX
function handleFormSubmit(formId, alertId, btnId) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById(btnId);
        const alertBox = document.getElementById(alertId);
        const originalText = btn.innerHTML;

        btn.innerHTML = 'Processing...';
        btn.disabled = true;

        const formData = new FormData(form);

        fetch('login_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            alertBox.style.display = 'block';
            alertBox.className = 'alert ' + data.status;
            alertBox.innerHTML = data.message;

            if (data.status === 'success') {
                if (formId.includes('Signup')) {
                    form.reset();
                    // Optional: auto switch to login
                } else {
                    // Redirect to dashboard (assuming dashboard.php exists, or reload)
                    setTimeout(() => {
                        window.location.href = 'dashboard.php'; 
                    }, 1500);
                }
            }
        })
        .catch(error => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alertBox.style.display = 'block';
            alertBox.className = 'alert error';
            alertBox.innerHTML = 'An error occurred. Please try again.';
        });
    });
}

// Initialize form handlers
document.addEventListener('DOMContentLoaded', () => {
    handleFormSubmit('staffLoginForm', 'staffLoginAlert', 'staffLoginBtn');
    handleFormSubmit('staffSignupForm', 'staffSignupAlert', 'staffSignupBtn');
    
    handleFormSubmit('hodLoginForm', 'hodLoginAlert', 'hodLoginBtn');
    handleFormSubmit('hodSignupForm', 'hodSignupAlert', 'hodSignupBtn');
    
    handleFormSubmit('vpLoginForm', 'vpLoginAlert', 'vpLoginBtn');
    handleFormSubmit('adminLoginForm', 'adminLoginAlert', 'adminLoginBtn');
});

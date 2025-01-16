// Tab switching function: Changes between login and register forms
function switchTab(tab) {
    const loginForm = document.getElementById('loginForm'); // Get the login form
    const registerForm = document.getElementById('registerForm'); // Get the register form
    const buttons = document.querySelectorAll('.tab-btn'); // Get the tab buttons

    // Switch to login tab
    if (tab === 'login') {
        loginForm.style.display = 'block'; // Show the login form
        registerForm.style.display = 'none'; // Hide the register form
        buttons[0].classList.add('active'); // Activate the login tab button
        buttons[1].classList.remove('active'); // Deactivate the register tab button
    } else {
        // Switch to register tab
        loginForm.style.display = 'none'; // Hide the login form
        registerForm.style.display = 'block'; // Show the register form
        buttons[0].classList.remove('active'); // Deactivate the login tab button
        buttons[1].classList.add('active'); // Activate the register tab button
    }
}

// Password visibility toggle function: Changes the input type for password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId); // Get the password input field
    const button = input.nextElementSibling; // Get the toggle button next to the input
    input.type = input.type === 'password' ? 'text' : 'password'; // Toggle input type
    button.textContent = input.type === 'password' ? '👁️' : '👁️‍🗨️'; // Change button icon
}

// Toast notifications function: Displays notifications with messages and types
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast'); // Get the toast element
    toast.textContent = message; // Set the toast message
    toast.className = `toast ${type}`; // Set the toast class based on type (success, error, etc.)
    toast.style.animation = 'none'; // Reset any previous animations
    toast.offsetHeight; // Trigger reflow to restart animation
    toast.style.animation = 'slideInOut 3s ease forwards'; // Apply animation for showing the toast
}

// Email validation function: Checks if the email format is valid
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); // Regular expression to validate email format
}

// Password validation function: Checks if the password meets various security criteria
function validatePassword(password) {
    const minLength = 8; // Minimum length for a password
    const hasUpperCase = /[A-Z]/.test(password); // Check for uppercase letters
    const hasLowerCase = /[a-z]/.test(password); // Check for lowercase letters
    const hasNumbers = /\d/.test(password); // Check for numbers
    const hasSpecialChar = /[!@#$%^&*]/.test(password); // Check for special characters

    // Continue password validation and calculate strength
    let strength = 0;

    if (password.length >= minLength) strength++; // Strength if length is 8 or more
    if (hasUpperCase) strength++; // Strength if there are uppercase letters
    if (hasLowerCase) strength++; // Strength if there are lowercase letters
    if (hasNumbers) strength++; // Strength if there are numbers
    if (hasSpecialChar) strength++; // Strength if there are special characters

    return strength; // Return the calculated strength value
}

// Password strength indicator update function: Updates the password strength bar based on the password strength
function updatePasswordStrength(password) {
    const strengthBar = document.querySelector('.password-strength-bar'); // Get the strength bar element
    const strength = validatePassword(password); // Get the password strength

    // Update the strength bar based on the strength value
    if (strength === 0) {
        strengthBar.style.width = '0%';
        strengthBar.style.backgroundColor = '#ddd'; // Gray for no strength
    } else if (strength <= 2) {
        strengthBar.style.width = '40%';
        strengthBar.style.backgroundColor = '#EF4444'; // Red for weak password
    } else if (strength <= 4) {
        strengthBar.style.width = '70%';
        strengthBar.style.backgroundColor = '#F59E0B'; // Yellow for medium strength
    } else {
        strengthBar.style.width = '100%';
        strengthBar.style.backgroundColor = '#10B981'; // Green for strong password
    }
}

// Event listeners for password input fields: Handles real-time password validation
const regPassword = document.getElementById('registerPassword'); // Get the password input for registration
const regConfirmPassword = document.getElementById('registerConfirmPassword'); // Get the confirm password input
regPassword.addEventListener('input', () => updatePasswordStrength(regPassword.value)); // Listen for password input change
regConfirmPassword.addEventListener('input', () => {
    // Validate if confirm password matches the original password
    if (regConfirmPassword.value !== regPassword.value) {
        regConfirmPassword.setCustomValidity("Passwords don't match"); // Set error if passwords don't match
    } else {
        regConfirmPassword.setCustomValidity(""); // Clear error if passwords match
    }
});

// Handle register form submission
document.getElementById('registerForm').addEventListener('submit', async function (event) {
    event.preventDefault(); // Prevent default submission for AJAX handling

    const name = document.getElementById('registerName').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('registerConfirmPassword').value;

    // Validate inputs
    if (!validateEmail(email)) {
        showToast('Invalid email address.', 'error');
        return;
    }
    if (password !== confirmPassword) {
        showToast('Passwords do not match.', 'error');
        return;
    }
    if (validatePassword(password) < 3) {
        showToast('Password is too weak.', 'error');
        return;
    }

    // Submit the form using fetch
    try {
        const response = await fetch('pages/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ regName: name, regEmail: email, regPassword: password, regConfirmPassword: confirmPassword }),
        });

        if (response.ok) {
            const result = await response.text();
            showToast('Registration successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 3000);
        } else {
            const error = await response.text();
            showToast(error || 'Registration failed.', 'error');
        }
    } catch (err) {
        showToast('Error communicating with the server.', 'error');
    }
});

// Handle login form submission
document.getElementById('loginForm').addEventListener('submit', async function (event) {
    event.preventDefault(); // Prevent default submission for AJAX handling

    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    // Validate inputs
    if (!validateEmail(email)) {
        showToast('Invalid email address.', 'error');
        return;
    }

    try {
        const response = await fetch('pages/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ email: email, password: password }),
        });

        if (response.ok) {
            const result = await response.text();
            showToast('Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 3000);
        } else {
            const error = await response.text();
            showToast(error || 'Login failed.', 'error');
        }
    } catch (err) {
        showToast('Error communicating with the server.', 'error');
    }
});

// Social login function: Placeholder for social login functionality
function socialLogin(provider) {
    showToast(`Continue with ${provider.charAt(0).toUpperCase() + provider.slice(1)}`, 'info'); // Show info toast for social login
}

// Initial Tab: Set the initial tab to 'login'
switchTab('login');

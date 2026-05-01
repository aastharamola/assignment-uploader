$(document).ready(function() {
    const registerForm = $('#registerForm');
    const authApiUrl = '/assignment-uploader/backend/api/auth.php';

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const nameRegex = /^[a-zA-Z\s]{2,}$/;
    const rollRegex = /^[a-zA-Z0-9]{3,}$/;

    registerForm.on('submit', function(e) {
        e.preventDefault();
        clearAllErrors();

        const formData = getFormData();
        const isValid = validateForm(formData);

        if (!isValid) {
            return;
        }

        registerUser(formData);
    });

    $('#email').on('blur', function() {
        const email = $(this).val().trim();

        if (email && !emailRegex.test(email)) {
            showFieldError('emailError', 'Invalid email format');
        } else {
            hideFieldError('emailError');
        }
    });

    $('#confirmPassword').on('input', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();

        if (confirmPassword && password !== confirmPassword) {
            showFieldError('confirmError', 'Passwords do not match');
        } else {
            hideFieldError('confirmError');
        }
    });

    $('input, select').on('focus', function() {
        const fieldId = $(this).attr('id');
        if (fieldId) {
            hideFieldError(fieldId + 'Error');
        }
        hideFieldError('formError');
    });

    $('#role').on('change', function() {
        hideFieldError('roleError');
    });

    $('#terms').on('change', function() {
        if ($(this).is(':checked')) {
            hideFieldError('termsError');
        }
    });

    function getFormData() {
        return {
            firstName: $('#firstName').val().trim(),
            lastName: $('#lastName').val().trim(),
            email: $('#email').val().trim(),
            rollNumber: $('#rollNumber').val().trim(),
            password: $('#password').val(),
            confirmPassword: $('#confirmPassword').val(),
            role: $('#role').val(),
            termsAccepted: $('#terms').is(':checked')
        };
    }

    function validateForm(formData) {
        let isValid = true;

        if (!formData.firstName) {
            showFieldError('firstNameError', 'First name is required');
            isValid = false;
        } else if (!nameRegex.test(formData.firstName)) {
            showFieldError('firstNameError', 'First name should contain only letters');
            isValid = false;
        }

        if (!formData.lastName) {
            showFieldError('lastNameError', 'Last name is required');
            isValid = false;
        } else if (!nameRegex.test(formData.lastName)) {
            showFieldError('lastNameError', 'Last name should contain only letters');
            isValid = false;
        }

        if (!formData.email) {
            showFieldError('emailError', 'Email is required');
            isValid = false;
        } else if (!emailRegex.test(formData.email)) {
            showFieldError('emailError', 'Please enter a valid email');
            isValid = false;
        }

        if (!formData.rollNumber) {
            showFieldError('rollError', 'Roll/ID number is required');
            isValid = false;
        } else if (!rollRegex.test(formData.rollNumber)) {
            showFieldError('rollError', 'Roll/ID should contain letters and numbers');
            isValid = false;
        }

        if (!formData.password) {
            showFieldError('passwordError', 'Password is required');
            isValid = false;
        } else if (formData.password.length < 8) {
            showFieldError('passwordError', 'Password must be at least 8 characters');
            isValid = false;
        } else if (!hasStrongPassword(formData.password)) {
            showFieldError('passwordError', 'Password must contain uppercase, lowercase, and numbers');
            isValid = false;
        }

        if (!formData.confirmPassword) {
            showFieldError('confirmError', 'Please confirm your password');
            isValid = false;
        } else if (formData.password !== formData.confirmPassword) {
            showFieldError('confirmError', 'Passwords do not match');
            isValid = false;
        }

        if (!formData.role) {
            showFieldError('roleError', 'Please select a role');
            isValid = false;
        }

        if (!formData.termsAccepted) {
            showFieldError('termsError', 'You must accept the terms and conditions');
            isValid = false;
        }

        return isValid;
    }

    function registerUser(formData) {
        const submitBtn = registerForm.find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.text('Creating Account...').prop('disabled', true);

        $.ajax({
            url: authApiUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'register',
                firstName: formData.firstName,
                lastName: formData.lastName,
                email: formData.email,
                rollNumber: formData.rollNumber,
                password: formData.password,
                role: formData.role
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Account created successfully! Redirecting to login...');

                    setTimeout(function() {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    showFieldError('formError', response.message || 'Registration failed');
                }
            },
            error: function() {
                showFieldError('formError', 'Connection error. Please try again.');
            },
            complete: function() {
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    }

    function hasStrongPassword(password) {
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);

        return hasUpperCase && hasLowerCase && hasNumber;
    }

    function clearAllErrors() {
        $('.error-message').text('').removeClass('show');
    }

    function showFieldError(errorId, message) {
        $('#' + errorId).text(message).addClass('show');
    }

    function hideFieldError(errorId) {
        $('#' + errorId).text('').removeClass('show');
    }
});

function showAlert(type, message) {
    const alertHTML = `<div class="alert alert-${type}">${message}</div>`;
    $('body').prepend(alertHTML);

    setTimeout(function() {
        $('.alert').fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}

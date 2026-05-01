$(document).ready(function() {
    const loginForm = $('#loginForm');
    const AUTH_API_URL = '/assignment-uploader/backend/api/auth.php';

    
    const query = new URLSearchParams(window.location.search);
    const qEmail = query.get('email');
    const qPassword = query.get('password');
    const qRole = query.get('role');
    const qAuto = query.get('autologin');

    if (qEmail) $('#email').val(qEmail);
    if (qPassword) $('#password').val(qPassword);
    if (qRole) $('#role').val(qRole);

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    loginForm.on('submit', function(e) {
        e.preventDefault();

        $('.error-message').html('').removeClass('show');

        const email = $('#email').val().trim();
        const password = $('#password').val();
        const role = $('#role').val();

        let isValid = true;

        if (!email) {
            $('#emailError').text('Email is required').addClass('show');
            isValid = false;
        } else if (!emailRegex.test(email)) {
            $('#emailError').text('Please enter a valid email').addClass('show');
            isValid = false;
        }

        if (!password) {
            $('#passwordError').text('Password is required').addClass('show');
            isValid = false;
        } else if (password.length < 6) {
            $('#passwordError').text('Password must be at least 6 characters').addClass('show');
            isValid = false;
        }

        if (!role) {
            $('#formError').text('Please select a role').addClass('show');
            isValid = false;
        }

        if (isValid) {
            performLogin(email, password, role);
        }
    });

    function performLogin(email, password, role) {
        const submitBtn = loginForm.find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.text('Signing in...').prop('disabled', true);

        
        $.ajax({
            url: AUTH_API_URL,
            type: 'POST',
            data: {
                action: 'login',
                email: email,
                password: password,
                role: role
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    localStorage.setItem('userEmail', email);
                    localStorage.setItem('userRole', role);
                    localStorage.setItem('token', response.token);

                    showAlert('success', 'Login successful');

                    setTimeout(function() {
                        window.location.href = 'dashboard.html';
                    }, 1500);
                } else {
                    $('#formError').text(response.message || 'Login failed').addClass('show');
                }
            },
            error: function() {
                $('#formError').text('Connection error. Please try again.').addClass('show');
            },
            complete: function() {
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    }

    
    $('#email').on('blur', function() {
        const email = $(this).val().trim();
        if (email && !emailRegex.test(email)) {
            $('#emailError').text('Invalid email format').addClass('show');
        } else {
            $('#emailError').text('').removeClass('show');
        }
    });

    $('input, select').on('focus', function() {
        $(this).attr('id') && $(`#${$(this).attr('id')}Error`).text('').removeClass('show');
        $('#formError').text('').removeClass('show');
    });

    if (localStorage.getItem('rememberEmail')) {
        $('#email').val(localStorage.getItem('rememberEmail'));
        $('#rememberMe').prop('checked', true);
    }

    $('#rememberMe').on('change', function() {
        if ($(this).is(':checked')) {
            localStorage.setItem('rememberEmail', $('#email').val());
        } else {
            localStorage.removeItem('rememberEmail');
        }
    });

    if (qAuto === '1' && qEmail && qPassword && qRole) {
        loginForm.trigger('submit');
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

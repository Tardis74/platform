/**
 * Логика для страниц входа и регистрации.
 * Обрабатывает отправку форм, валидацию, вызов API и редирект.
 */

document.addEventListener('DOMContentLoaded', function() {
    // --- Обработка формы входа ---
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                showToast('Заполните все поля', 'warning');
                return;
            }

            showLoading(true);
            try {
                const data = await apiCall('auth.login', { email, password });
                if (data.token) {
                    localStorage.setItem('jwt', data.token);
                    const user = await getUser();
                    showToast('Вход выполнен успешно', 'success');
                    // Определяем дашборд по роли
                    let dashboard = '/parent/dashboard';
                    if (user && user.role) {
                        dashboard = '/' + user.role + '/dashboard';
                    }
                    window.location.href = data.redirect || '/' + user.role + '/dashboard';
                } else {
                    showToast('Ошибка входа: токен не получен', 'danger');
                }
            } catch (error) {
                showToast(error.message, 'danger');
            } finally {
                showLoading(false);
            }
        });
    }

    // --- Обработка формы регистрации ---
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const full_name = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const password_confirm = document.getElementById('password_confirm').value;
            const consent = document.getElementById('consent').checked;

            // Валидация
            if (!full_name || !email || !password || !password_confirm) {
                showToast('Заполните все поля', 'warning');
                return;
            }
            if (password !== password_confirm) {
                showToast('Пароли не совпадают', 'warning');
                return;
            }
            if (password.length < 6) {
                showToast('Пароль должен быть не менее 6 символов', 'warning');
                return;
            }
            if (!consent) {
                showToast('Необходимо согласие на обработку персональных данных', 'warning');
                return;
            }

            showLoading(true);
            try {
                const data = await apiCall('auth.register', {
                    full_name,
                    email,
                    password,
                    consent: true // сервер ожидает boolean
                });
                if (data.token) {
                    localStorage.setItem('jwt', data.token);
                    await getUser();
                    showToast('Регистрация успешна', 'success');
                    window.location.href = '/parent/dashboard';
                } else {
                    showToast('Ошибка регистрации', 'danger');
                }
            } catch (error) {
                showToast(error.message, 'danger');
            } finally {
                showLoading(false);
            }
        });
    }

    // --- Кнопка выхода (если присутствует на странице) ---
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            logout();
        });
    }

    // --- Инициализация страницы (вызывается из main.php) ---
    window.initPage = async function() {
        const token = localStorage.getItem('jwt');
        const currentPath = window.location.pathname;

        // Если токена нет и мы не на странице аутентификации – редирект на логин
        if (!token) {
            if (!currentPath.startsWith('/auth/')) {
                window.location.href = '/auth/login';
            }
            return;
        }

        try {
            const user = await getUser();
            if (user) {
                // Показываем информацию о пользователе в шапке
                const userInfo = document.getElementById('user-info');
                const userName = document.getElementById('user-name');
                if (userInfo && userName) {
                    userInfo.classList.remove('d-none');
                    userName.textContent = user.full_name || user.email;
                }

                // Если мы на странице аутентификации – редирект на дашборд
                if (currentPath.startsWith('/auth/')) {
                    const dashboard = '/' + user.role + '/dashboard';
                    window.location.href = dashboard;
                }

                // Здесь можно добавить заполнение навигационного меню в зависимости от роли
                // ...
            } else {
                // Пользователь не получен – токен невалиден
                localStorage.removeItem('jwt');
                window.location.href = '/auth/login';
            }
        } catch (e) {
            console.error('Ошибка инициализации:', e);
            localStorage.removeItem('jwt');
            window.location.href = '/auth/login';
        }
    };
});
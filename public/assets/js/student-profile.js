/**
 * Профиль ученика и смена пароля.
 */
async function initPage() {
    await loadProfile();
    setupEventListeners();

    // Проверка, если first_login = true, показываем уведомление
    try {
        const profile = await apiCall('student.profile');
        if (profile.first_login) {
            showToast('Необходимо сменить пароль', 'warning');
            // Можно автоматически показать поле текущего пароля? Но по ТЗ текущий пароль не требуется при первом входе.
            // В форме мы скрываем поле current-password-group, если first_login.
            document.getElementById('current-password-group').style.display = 'none';
        } else {
            document.getElementById('current-password-group').style.display = 'block';
        }
    } catch {}
}

async function loadProfile() {
    try {
        const profile = await apiCall('student.profile');
        document.getElementById('profile-fullname').textContent = profile.full_name || '—';
        document.getElementById('profile-class').textContent = profile.class_name || '—';
        document.getElementById('profile-email').textContent = profile.email || '—';
        document.getElementById('profile-dormitory').textContent = profile.is_dormitory ? 'Проживает в общежитии' : 'Не проживает';
        document.getElementById('profile-points').textContent = profile.total_points || 0;
    } catch (e) {
        showToast('Ошибка загрузки профиля: ' + e.message, 'danger');
    }
}

function setupEventListeners() {
    document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword.length < 6) {
            showToast('Новый пароль должен быть не менее 6 символов', 'warning');
            return;
        }
        if (newPassword !== confirmPassword) {
            showToast('Пароли не совпадают', 'warning');
            return;
        }

        // Если поле текущего пароля видимо, проверяем его
        const currentGroup = document.getElementById('current-password-group');
        if (currentGroup.style.display !== 'none' && !currentPassword) {
            showToast('Введите текущий пароль', 'warning');
            return;
        }

        const payload = {
            new_password: newPassword
        };
        if (currentGroup.style.display !== 'none') {
            payload.current_password = currentPassword;
        }

        showLoading(true);
        try {
            await apiCall('student.changePassword', payload);
            showToast('Пароль изменён', 'success');
            // Если был первый вход, перенаправляем на дашборд
            const profile = await apiCall('student.profile');
            if (profile.first_login === false) {
                // Пароль изменён, first_login стал false, редирект
                window.location.href = '/student/dashboard';
            } else {
                // Просто обновляем страницу
                await loadProfile();
            }
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
}
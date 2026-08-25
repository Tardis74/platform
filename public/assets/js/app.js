/**
 * Глобальные функции для работы с API, уведомлениями, загрузкой, аутентификацией.
 */

window.user = null;

/**
 * Универсальная функция для вызова API.
 * @param {string} method – имя метода (например, auth.login)
 * @param {Object} data – данные для отправки (JSON)
 * @returns {Promise<any>} – данные из ответа (data)
 * @throws {Error} – если ответ содержит ошибку или статус 401
 */
async function apiCall(method, data = {}) {
    const token = localStorage.getItem('jwt');
    const headers = {
        'Content-Type': 'application/json',
    };
    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }
    const url = '/api.php?method=' + encodeURIComponent(method);
    const response = await fetch(url, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(data),
    });
    const result = await response.json();
    if (!result.success) {
        // Если вернулся 401 – токен недействителен, удаляем и перенаправляем на логин
        if (response.status === 401) {
            localStorage.removeItem('jwt');
            window.location.href = '/auth/login';
            return;
        }
        throw new Error(result.error || 'Unknown error');
    }
    return result.data;
}

/**
 * Показывает всплывающее уведомление (тост).
 * @param {string} message – текст сообщения
 * @param {string} type – тип: 'success', 'error', 'warning', 'info' (по умолчанию 'info')
 */
function showToast(message, type = 'info') {
    // Создаём контейнер для тостов, если его нет
    const container = document.getElementById('toast-container') || (() => {
        const div = document.createElement('div');
        div.id = 'toast-container';
        div.className = 'position-fixed bottom-0 end-0 p-3';
        div.style.zIndex = 1050;
        document.body.appendChild(div);
        return div;
    })();

    const toast = document.createElement('div');
    // Цвета Bootstrap: bg-success, bg-danger, bg-warning, bg-info
    toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    container.appendChild(toast);

    // Автоматическое исчезновение через 5 секунд
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * Показывает или скрывает индикатор загрузки.
 * @param {boolean} show – true = показать, false = скрыть
 */
function showLoading(show = true) {
    let overlay = document.getElementById('loading-overlay');
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'd-flex justify-content-center align-items-center';
            overlay.innerHTML = `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div>`;
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    } else {
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
}

/**
 * Выход из системы – удаляет токен, очищает данные пользователя, перенаправляет на логин.
 */
function logout() {
    localStorage.removeItem('jwt');
    window.user = null;
    window.location.href = '/auth/login';
}

/**
 * Возвращает данные текущего пользователя из кэша или запрашивает с сервера.
 * @returns {Promise<Object|null>} – объект пользователя или null
 */
async function getUser() {
    if (window.user) {
        return window.user;
    }
    try {
        const data = await apiCall('auth.check');
        window.user = data.user;
        return window.user;
    } catch (e) {
        console.error('getUser error:', e);
        return null;
    }
}
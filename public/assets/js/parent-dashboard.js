/**
 * Инициализация дашборда родителя.
 */
document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadChildren();
    await loadNotifications();
    setupModals();
}

async function loadChildren() {
    try {
        const children = await apiCall('parent.getChildren');
        const container = document.getElementById('children-list');
        if (!children || children.length === 0) {
            container.innerHTML = '<div class="col-12 text-muted">Нет привязанных детей.</div>';
            return;
        }
        container.innerHTML = children.map(child => `
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-light rounded-circle p-3" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="card-title">${child.full_name}</h5>
                                <p class="card-text text-muted mb-0">${child.class_name || 'Класс не указан'}</p>
                                <span class="badge ${child.status === 'active' ? 'bg-success' : 'bg-warning'}">
                                    ${child.status === 'active' ? 'Подтверждён' : 'Ожидает'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (e) {
        showToast('Ошибка загрузки детей: ' + e.message, 'danger');
    }
}

async function loadNotifications() {
    try {
        // Используем метод parent.getNotifications, если он есть, иначе генерируем из данных детей
        let notifications = [];
        try {
            notifications = await apiCall('parent.getNotifications');
        } catch {
            // Если метод отсутствует, создаём заглушку
            const children = await apiCall('parent.getChildren');
            notifications = children.map(c => ({
                text: `${c.full_name} ${c.status === 'active' ? 'подтверждён' : 'ожидает подтверждения'}`,
                time: new Date().toLocaleString(),
                icon: c.status === 'active' ? '✅' : '⏳'
            }));
        }
        const container = document.getElementById('notifications-list');
        if (!notifications || notifications.length === 0) {
            container.innerHTML = '<div class="list-group-item text-muted">Нет уведомлений</div>';
            return;
        }
        container.innerHTML = notifications.map(n => `
            <div class="list-group-item d-flex align-items-center">
                <span class="me-2">${n.icon || '📌'}</span>
                <span class="flex-grow-1">${n.text}</span>
                <small class="text-muted">${n.time || ''}</small>
            </div>
        `).join('');
    } catch (e) {
        showToast('Ошибка загрузки уведомлений: ' + e.message, 'danger');
    }
}

function setupModals() {
    // Обработчик формы добавления ребёнка
    document.getElementById('addChildForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const snils = document.getElementById('child_snils').value.trim();
        const full_name = document.getElementById('child_full_name').value.trim();
        const class_id = document.getElementById('child_class').value;
        const birth_date = document.getElementById('child_birth_date').value;
        const is_dormitory = document.getElementById('child_is_dormitory').checked;

        if (!/^\d{11}$/.test(snils)) {
            showToast('СНИЛС должен содержать 11 цифр', 'warning');
            return;
        }
        if (!full_name) {
            showToast('Введите ФИО', 'warning');
            return;
        }

        showLoading(true);
        try {
            await apiCall('parent.addChild', { snils, full_name, class_id, birth_date, is_dormitory });
            showToast('Ребёнок добавлен успешно', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addChildModal')).hide();
            this.reset();
            await loadChildren();
            await loadNotifications();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Обработчик формы привязки
    document.getElementById('linkChildForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const snils = document.getElementById('link_snils').value.trim();
        if (!/^\d{11}$/.test(snils)) {
            showToast('СНИЛС должен содержать 11 цифр', 'warning');
            return;
        }
        showLoading(true);
        try {
            await apiCall('parent.linkChild', { snils });
            showToast('Запрос на привязку отправлен', 'success');
            bootstrap.Modal.getInstance(document.getElementById('linkChildModal')).hide();
            this.reset();
            await loadChildren();
            await loadNotifications();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Загрузка списка классов при открытии модалки добавления
    document.getElementById('addChildModal')?.addEventListener('show.bs.modal', async function() {
        try {
            const classes = await apiCall('admin.class.list');
            const select = document.getElementById('child_class');
            select.innerHTML = '<option value="">Не выбран</option>' + classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        } catch (e) {
            // Если метод не доступен, оставляем пустой
        }
    });
}
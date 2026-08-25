/**
 * Инициализация страницы управления детьми.
 */
document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadChildrenTable();
    setupModals();
}

async function loadChildrenTable() {
    try {
        const children = await apiCall('parent.getChildren');
        const tbody = document.getElementById('childrenTableBody');
        if (!children || children.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Нет привязанных детей.</td></tr>';
            return;
        }
        tbody.innerHTML = children.map(child => `
            <tr>
                <td>${child.full_name}</td>
                <td>${child.class_name || '—'}</td>
                <td>${child.birth_date || '—'}</td>
                <td><span class="badge ${child.status === 'active' ? 'bg-success' : 'bg-warning'}">${child.status === 'active' ? 'Подтверждён' : 'Ожидает'}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-info view-profile" data-id="${child.id}">Просмотр</button>
                </td>
            </tr>
        `).join('');
        // Обработчики для кнопок "Просмотр" (заглушка)
        document.querySelectorAll('.view-profile').forEach(btn => {
            btn.addEventListener('click', function() {
                showToast('Профиль ученика (заглушка)', 'info');
            });
        });
    } catch (e) {
        showToast('Ошибка загрузки списка детей: ' + e.message, 'danger');
    }
}

function setupModals() {
    // Аналогично dashboard – обработчики форм добавления и привязки
    document.getElementById('addChildForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const snils = document.getElementById('child_snils').value.trim();
        const full_name = document.getElementById('child_full_name').value.trim();
        const class_id = document.getElementById('child_class').value;
        const birth_date = document.getElementById('child_birth_date').value;
        const is_dormitory = document.getElementById('child_is_dormitory').checked;

        if (!/^\d{11}$/.test(snils)) { showToast('СНИЛС должен содержать 11 цифр', 'warning'); return; }
        if (!full_name) { showToast('Введите ФИО', 'warning'); return; }

        showLoading(true);
        try {
            await apiCall('parent.addChild', { snils, full_name, class_id, birth_date, is_dormitory });
            showToast('Ребёнок добавлен', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addChildModal')).hide();
            this.reset();
            await loadChildrenTable();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    document.getElementById('linkChildForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const snils = document.getElementById('link_snils').value.trim();
        if (!/^\d{11}$/.test(snils)) { showToast('СНИЛС должен содержать 11 цифр', 'warning'); return; }
        showLoading(true);
        try {
            await apiCall('parent.linkChild', { snils });
            showToast('Запрос на привязку отправлен', 'success');
            bootstrap.Modal.getInstance(document.getElementById('linkChildModal')).hide();
            this.reset();
            await loadChildrenTable();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Загрузка классов при открытии модалки
    document.getElementById('addChildModal')?.addEventListener('show.bs.modal', async function() {
        try {
            const classes = await apiCall('admin.class.list');
            const select = document.getElementById('child_class');
            select.innerHTML = '<option value="">Не выбран</option>' + classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        } catch (e) {
            // оставляем пустым
        }
    });
}
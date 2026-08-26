let allAchievements = [];
let categories = [];
let currentFilters = { student: '', category: '' };

/**
 * Загружает список категорий для фильтра.
 */
async function loadCategories() {
    try {
        categories = await apiCall('admin.categoryList');
        const select = document.getElementById('filter-category');
        select.innerHTML = '<option value="">Все категории</option>' +
            categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (error) {
        console.warn('Не удалось загрузить категории', error);
    }
}

/**
 * Загружает достижения на проверке.
 */
async function loadAchievements() {
    showLoading(true);
    try {
        allAchievements = await apiCall('moderator.getPendingAchievements');
        renderAchievements();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Рендерит таблицу достижений.
 */
function renderAchievements() {
    const tbody = document.getElementById('achievements-body');
    const filtered = allAchievements.filter(ach => {
        const studentMatch = ach.student_name.toLowerCase().includes(currentFilters.student.toLowerCase());
        const categoryMatch = currentFilters.category === '' || ach.category_id == currentFilters.category;
        return studentMatch && categoryMatch;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Нет достижений на проверке</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(ach => `
        <tr>
            <td>${ach.student_name}</td>
            <td>${ach.class_name || ''}</td>
            <td>${ach.category_name || ''}</td>
            <td>${ach.title}</td>
            <td>${new Date(ach.created_at).toLocaleString()}</td>
            <td>${ach.file_url ? `<a href="${ach.file_url}" target="_blank">Скачать</a>` : ''}</td>
            <td>
                <button class="btn btn-sm btn-success approve-ach" data-id="${ach.id}">Подтвердить</button>
                <button class="btn btn-sm btn-danger reject-ach" data-id="${ach.id}" data-bs-toggle="modal" data-bs-target="#rejectModal">Отклонить</button>
            </td>
        </tr>
    `).join('');

    document.querySelectorAll('.approve-ach').forEach(btn => {
        btn.addEventListener('click', function() {
            confirmAchievement(this.dataset.id);
        });
    });
    document.querySelectorAll('.reject-ach').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reject-achievement-id').value = this.dataset.id;
            document.getElementById('reject-comment').value = '';
        });
    });
}

/**
 * Подтверждение достижения.
 */
async function confirmAchievement(id) {
    showLoading(true);
    try {
        await apiCall('moderator.confirmAchievement', { achievement_id: id });
        showToast('Достижение подтверждено, баллы начислены', 'success');
        await loadAchievements();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Отклонение достижения.
 */
async function rejectAchievement() {
    const id = document.getElementById('reject-achievement-id').value;
    const comment = document.getElementById('reject-comment').value.trim();
    if (!comment) {
        showToast('Укажите причину отклонения', 'warning');
        return;
    }
    showLoading(true);
    try {
        await apiCall('moderator.rejectAchievement', { achievement_id: id, comment });
        showToast('Достижение отклонено', 'success');
        $('#rejectModal').modal('hide');
        await loadAchievements();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function initPage() {
    // Загружаем категории
    loadCategories();

    // Фильтры
    document.getElementById('filter-student').addEventListener('input', function() {
        currentFilters.student = this.value;
        renderAchievements();
    });
    document.getElementById('filter-category').addEventListener('change', function() {
        currentFilters.category = this.value;
        renderAchievements();
    });
    document.getElementById('clear-filters').addEventListener('click', function() {
        document.getElementById('filter-student').value = '';
        document.getElementById('filter-category').value = '';
        currentFilters.student = '';
        currentFilters.category = '';
        renderAchievements();
    });

    document.getElementById('confirm-reject').addEventListener('click', rejectAchievement);

    loadAchievements();
}
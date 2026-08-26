/**
 * Просмотр портфолио класса.
 */
let students = [];
let achievements = [];
let categories = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadStudents();
    await loadCategories();
    await loadYears();
    setupEventListeners();
    // По умолчанию выбираем первого ученика
    const select = document.getElementById('student-select');
    if (select.options.length > 1) {
        select.selectedIndex = 1;
        await loadPortfolio();
    }
}

async function loadStudents() {
    try {
        students = await apiCall('teacher.getStudents');
        const select = document.getElementById('student-select');
        select.innerHTML = '<option value="">Выберите ученика</option>';
        students.forEach(s => {
            select.innerHTML += `<option value="${s.id}">${s.full_name}</option>`;
        });
    } catch (e) {
        showToast('Ошибка загрузки учеников: ' + e.message, 'danger');
    }
}

async function loadCategories() {
    try {
        categories = await apiCall('admin.category.list');
        const select = document.getElementById('filter-category');
        select.innerHTML = '<option value="">Все</option>';
        categories.forEach(c => {
            select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
    } catch (e) {
        // игнорируем
    }
}

async function loadYears() {
    const currentYear = new Date().getFullYear();
    const select = document.getElementById('filter-year');
    select.innerHTML = '<option value="">Все</option>';
    for (let y = currentYear; y >= 2020; y--) {
        select.innerHTML += `<option value="${y}">${y}</option>`;
    }
}

async function loadPortfolio() {
    const studentId = document.getElementById('student-select').value;
    if (!studentId) {
        document.getElementById('portfolio-body').innerHTML = '<tr><td colspan="6" class="text-muted">Выберите ученика</td></tr>';
        document.getElementById('total-points').textContent = '0';
        return;
    }

    const category = document.getElementById('filter-category').value;
    const year = document.getElementById('filter-year').value;

    showLoading(true);
    try {
        // Используем метод student.getAchievements с параметром student_id (если поддерживается)
        // Или teacher.getStudentAchievements
        achievements = await apiCall('teacher.getStudentAchievements', {
            student_id: studentId,
            category_id: category || undefined,
            year: year || undefined
        });
        renderPortfolio();
    } catch (e) {
        showToast('Ошибка загрузки портфолио: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderPortfolio() {
    const tbody = document.getElementById('portfolio-body');
    if (!achievements || achievements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Нет достижений</td></tr>';
        document.getElementById('total-points').textContent = '0';
        return;
    }

    let total = 0;
    let html = '';
    achievements.forEach(a => {
        const statusMap = { pending: 'На проверке', approved: 'Подтверждено', rejected: 'Отклонено' };
        const statusClass = { pending: 'warning', approved: 'success', rejected: 'danger' }[a.status] || 'secondary';
        const points = a.status === 'approved' ? (a.points || 0) : '—';
        if (a.status === 'approved') total += (a.points || 0);

        let actions = '';
        if (a.status === 'pending') {
            actions = `
                <button class="btn btn-sm btn-success confirm-achievement" data-id="${a.id}">Подтвердить</button>
                <button class="btn btn-sm btn-danger reject-achievement" data-id="${a.id}">Отклонить</button>
            `;
        } else {
            actions = '—';
        }

        html += `
            <tr>
                <td>${a.title}</td>
                <td>${a.category_name || '—'}</td>
                <td><span class="badge bg-${statusClass}">${statusMap[a.status] || a.status}</span></td>
                <td>${points}</td>
                <td>${new Date(a.created_at).toLocaleDateString()}</td>
                <td>${actions}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
    document.getElementById('total-points').textContent = total;
}

function setupEventListeners() {
    document.getElementById('student-select').addEventListener('change', loadPortfolio);
    document.getElementById('apply-portfolio-filters').addEventListener('click', loadPortfolio);

    // Подтверждение достижения
    document.getElementById('portfolio-body').addEventListener('click', async function(e) {
        const target = e.target.closest('.confirm-achievement');
        if (target) {
            const achievementId = target.dataset.id;
            if (confirm('Подтвердить достижение?')) {
                showLoading(true);
                try {
                    await apiCall('moderator.confirmAchievement', { achievement_id: achievementId });
                    showToast('Достижение подтверждено', 'success');
                    await loadPortfolio();
                } catch (e) {
                    showToast(e.message, 'danger');
                } finally {
                    showLoading(false);
                }
            }
        }
    });

    // Отклонение достижения – открываем модалку
    document.getElementById('portfolio-body').addEventListener('click', function(e) {
        const target = e.target.closest('.reject-achievement');
        if (target) {
            const achievementId = target.dataset.id;
            document.getElementById('reject-achievement-id').value = achievementId;
            document.getElementById('reject-achievement-reason').value = '';
            const modal = new bootstrap.Modal(document.getElementById('reject-achievement-modal'));
            modal.show();
        }
    });

    // Подтверждение отклонения
    document.getElementById('confirm-reject-achievement').addEventListener('click', async function() {
        const achievementId = document.getElementById('reject-achievement-id').value;
        const reason = document.getElementById('reject-achievement-reason').value.trim();
        if (!reason) {
            showToast('Укажите причину отклонения', 'warning');
            return;
        }
        showLoading(true);
        try {
            await apiCall('moderator.rejectAchievement', { achievement_id: achievementId, comment: reason });
            showToast('Достижение отклонено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('reject-achievement-modal')).hide();
            await loadPortfolio();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
}
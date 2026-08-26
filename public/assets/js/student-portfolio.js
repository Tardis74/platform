/**
 * Портфолио ученика.
 */
let allAchievements = [];

async function initPage() {
    await loadCategories();
    await loadYearFilter();
    await loadPortfolioData();
    setupEventListeners();
}

async function loadCategories() {
    try {
        const categories = await apiCall('student.getAchievementCategories');
        const select = document.getElementById('filterCategoryPortfolio');
        select.innerHTML = '<option value="">Все</option>' + categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        // Также заполняем для модалки
        const modalSelect = document.getElementById('achievementCategory');
        modalSelect.innerHTML = '<option value="">Выберите...</option>' + categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
        showToast('Не удалось загрузить категории', 'danger');
    }
}

async function loadYearFilter() {
    // Заполняем выпадающий список годов (от 2020 до текущего)
    const currentYear = new Date().getFullYear();
    const select = document.getElementById('filterYear');
    select.innerHTML = '<option value="">Все</option>';
    for (let y = currentYear; y >= 2020; y--) {
        select.innerHTML += `<option value="${y}">${y}</option>`;
    }
}

async function loadPortfolioData() {
    const category = document.getElementById('filterCategoryPortfolio').value;
    const year = document.getElementById('filterYear').value;
    const status = document.getElementById('filterStatusPortfolio').value;

    showLoading(true);
    try {
        // Получаем достижения с фильтрацией (если API поддерживает)
        const params = {};
        if (category) params.category_id = category;
        if (year) params.year = year;
        if (status) params.status = status;
        allAchievements = await apiCall('student.getAchievements', params);

        // Обновляем общую сумму баллов и место
        const profile = await apiCall('student.profile');
        document.getElementById('portfolio-total-points').textContent = profile.total_points || 0;
        try {
            const rating = await apiCall('student.getRatingPlace');
            document.getElementById('portfolio-rating-place').textContent = rating.place !== null ? rating.place : '—';
        } catch {
            document.getElementById('portfolio-rating-place').textContent = '—';
        }

        renderAchievements(allAchievements);
    } catch (e) {
        showToast('Ошибка загрузки портфолио: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderAchievements(achievements) {
    const tbody = document.getElementById('achievements-body');
    if (!achievements || achievements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Нет достижений</td></tr>';
        return;
    }
    tbody.innerHTML = achievements.map(a => {
        const statusMap = {
            'pending': 'На проверке',
            'approved': 'Подтверждено',
            'rejected': 'Отклонено'
        };
        const statusClass = {
            'pending': 'warning',
            'approved': 'success',
            'rejected': 'danger'
        }[a.status] || 'secondary';
        return `
            <tr>
                <td>${a.title}</td>
                <td>${a.category_name || '—'}</td>
                <td><span class="badge bg-${statusClass}">${statusMap[a.status] || a.status}</span></td>
                <td>${a.status === 'approved' ? (a.points || 0) : '—'}</td>
                <td>${new Date(a.created_at).toLocaleDateString()}</td>
            </tr>
        `;
    }).join('');
}

function setupEventListeners() {
    document.getElementById('applyPortfolioFilters').addEventListener('click', loadPortfolioData);

    // Форма добавления достижения
    document.getElementById('addAchievementForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const categoryId = document.getElementById('achievementCategory').value;
        const title = document.getElementById('achievementTitle').value.trim();
        const description = document.getElementById('achievementDescription').value.trim();
        const fileInput = document.getElementById('achievementFile');

        if (!categoryId || !title || !fileInput.files.length) {
            showToast('Заполните все обязательные поля', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('category_id', categoryId);
        formData.append('title', title);
        if (description) formData.append('description', description);
        formData.append('file', fileInput.files[0]);

        showLoading(true);
        try {
            const token = localStorage.getItem('jwt');
            const response = await fetch('/api.php?method=achievement.add', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                body: formData
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Ошибка добавления');
            }
            showToast('Достижение отправлено на проверку', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addAchievementModal')).hide();
            this.reset();
            // Обновить список
            await loadPortfolioData();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
}
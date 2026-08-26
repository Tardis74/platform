/**
 * Страница мероприятий.
 */
let currentPage = 1;
let totalPages = 1;
let filters = {};

async function initPage() {
    await loadCategories();
    await applyFilters();
    setupEventListeners();
}

async function loadCategories() {
    try {
        // Для фильтра категорий используем admin.category.list или event.category.list.
        // Поскольку у нас нет отдельного метода для студента, используем admin.category.list (он доступен для всех авторизованных).
        const categories = await apiCall('admin.category.list');
        const select = document.getElementById('filterCategory');
        select.innerHTML = '<option value="">Все</option>' + categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
        // Если метод недоступен, оставляем пустой
    }
}

async function applyFilters() {
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    const category = document.getElementById('filterCategory').value;
    const regStatus = document.getElementById('filterRegistrationStatus').value;

    filters = {
        start_date: dateFrom || undefined,
        end_date: dateTo || undefined,
        category_id: category || undefined,
        registration_status: regStatus,
        page: currentPage,
        limit: 10
    };

    showLoading(true);
    try {
        const data = await apiCall('student.getAvailableEvents', filters);
        // Предполагаем, что ответ содержит массив мероприятий и, возможно, пагинацию.
        // Если пагинация не возвращается, мы можем просто отобразить список.
        renderEvents(data);
        // Если нужно, обновляем пагинацию.
        // Для простоты пагинацию можно сделать на основе общего количества.
        // Но для упрощения оставим только список.
    } catch (e) {
        showToast('Ошибка загрузки мероприятий: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderEvents(events) {
    const container = document.getElementById('events-list');
    if (!events || events.length === 0) {
        container.innerHTML = '<div class="col-12 text-muted">Мероприятий не найдено.</div>';
        return;
    }
    container.innerHTML = events.map(e => {
        const statusBadge = e.is_registered ? 
            `<span class="badge bg-${e.registration_status === 'approved' ? 'success' : 'warning'}">${e.registration_status}</span>` :
            `<span class="badge bg-secondary">не записан</span>`;
        const button = e.is_registered ?
            `<button class="btn btn-sm btn-danger unregister-btn" data-event-id="${e.id}">Отменить запись</button>` :
            `<button class="btn btn-sm btn-primary register-btn" data-event-id="${e.id}">Записаться</button>`;
        return `
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${e.title}</h5>
                        <p class="card-text">
                            <i class="bi bi-calendar-event"></i> ${new Date(e.start_datetime).toLocaleString()}
                            ${e.end_datetime ? ' - ' + new Date(e.end_datetime).toLocaleString() : ''}
                        </p>
                        <p class="card-text"><i class="bi bi-geo-alt"></i> ${e.location || 'Место не указано'}</p>
                        <p class="card-text">Свободных мест: ${e.max_participants ? e.max_participants - (e.current_count || 0) : '∞'}</p>
                        <p class="card-text">Баллы: ${e.points || 0}</p>
                        <p>${statusBadge}</p>
                        <div class="d-flex gap-2">
                            <a href="/student/event?id=${e.id}" class="btn btn-sm btn-outline-primary">Подробнее</a>
                            ${button}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Обработчики для кнопок записи/отмены
    container.querySelectorAll('.register-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const eventId = this.dataset.eventId;
            await registerForEvent(eventId);
        });
    });
    container.querySelectorAll('.unregister-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const eventId = this.dataset.eventId;
            await unregisterFromEvent(eventId);
        });
    });
}

async function registerForEvent(eventId) {
    showLoading(true);
    try {
        await apiCall('student.registerForEvent', { event_id: eventId });
        showToast('Вы записаны на мероприятие', 'success');
        await applyFilters(); // обновить список
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

async function unregisterFromEvent(eventId) {
    showLoading(true);
    try {
        await apiCall('student.unregisterForEvent', { event_id: eventId });
        showToast('Запись отменена', 'success');
        await applyFilters();
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function setupEventListeners() {
    document.getElementById('applyFilters').addEventListener('click', () => {
        currentPage = 1;
        applyFilters();
    });
    // Можно добавить пагинацию (кнопки "Следующая" и т.д.)
}
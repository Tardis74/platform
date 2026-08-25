/**
 * Страница мероприятий – фильтры и список.
 */
let allEvents = [];
let children = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadChildrenForFilter();
    await applyFilters();
    setupEventListeners();
}

async function loadChildrenForFilter() {
    try {
        children = await apiCall('parent.getChildren');
        const select = document.getElementById('filterChild');
        select.innerHTML = '<option value="">Все дети</option>' +
            children.map(c => `<option value="${c.id}">${c.full_name}</option>`).join('');
    } catch (e) {
        showToast('Не удалось загрузить детей', 'danger');
    }
}

async function applyFilters() {
    const childId = document.getElementById('filterChild').value;
    const status = document.getElementById('filterStatus').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    showLoading(true);
    try {
        // Получаем мероприятия всех детей с фильтром по дате (если есть)
        let params = {};
        if (dateFrom) params.start_date = dateFrom;
        if (dateTo) params.end_date = dateTo;
        if (childId) params.child_id = childId;
        // Для фильтрации по статусу используем клиентскую фильтрацию, т.к. API не поддерживает
        allEvents = await apiCall('parent.getChildrenEvents', params);
        renderEvents(allEvents, status);
    } catch (e) {
        showToast('Ошибка загрузки мероприятий: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderEvents(events, statusFilter) {
    const container = document.getElementById('eventsList');
    let filtered = events;
    if (statusFilter && statusFilter !== 'all') {
        filtered = events.filter(e => e.status === statusFilter);
    }
    if (!filtered || filtered.length === 0) {
        container.innerHTML = '<div class="col-12 text-muted">Нет мероприятий</div>';
        return;
    }
    container.innerHTML = filtered.map(e => {
        const statusClass = {
            'pending': 'warning',
            'approved': 'success',
            'rejected': 'danger',
            'cancelled': 'secondary'
        }[e.status] || 'secondary';
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
                        <p><span class="badge bg-${statusClass}">${e.status}</span></p>
                        <a href="/parent/event?id=${e.event_id || e.id}" class="btn btn-sm btn-outline-primary">Подробнее</a>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function setupEventListeners() {
    document.getElementById('applyFilters')?.addEventListener('click', applyFilters);
    // Также применяем фильтры при изменении select (можно добавить по желанию)
}
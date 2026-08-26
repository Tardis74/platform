/**
 * Управление мероприятиями класса.
 */
let events = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadEvents();
    setupEventListeners();
}

async function loadEvents() {
    showLoading(true);
    try {
        events = await apiCall('event.list', { status: 'active' }); // фильтр по классу будет на бэке
        renderEvents();
    } catch (e) {
        showToast('Ошибка загрузки мероприятий: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderEvents() {
    const container = document.getElementById('events-list');
    if (!events || events.length === 0) {
        container.innerHTML = '<p class="text-muted">Нет мероприятий.</p>';
        return;
    }

    let html = '<div class="row">';
    events.forEach(e => {
        const statusClass = { active: 'success', cancelled: 'secondary', completed: 'info' }[e.status] || 'secondary';
        const canEdit = e.status === 'active' && new Date(e.start_datetime) > new Date();
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${e.title}</h5>
                        <p class="card-text">${e.description || ''}</p>
                        <p><i class="bi bi-calendar-event"></i> ${new Date(e.start_datetime).toLocaleString()}</p>
                        <p><i class="bi bi-geo-alt"></i> ${e.location || '—'}</p>
                        <p><span class="badge bg-${statusClass}">${e.status}</span></p>
                        <div class="d-flex gap-2">
                            ${canEdit ? `<button class="btn btn-sm btn-outline-primary edit-event" data-id="${e.id}">Редактировать</button>` : ''}
                            ${canEdit ? `<button class="btn btn-sm btn-outline-danger cancel-event" data-id="${e.id}">Отменить</button>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function setupEventListeners() {
    // Создание/редактирование
    document.getElementById('save-event').addEventListener('click', async function() {
        const eventId = document.getElementById('event-id').value;
        const title = document.getElementById('event-title').value.trim();
        const description = document.getElementById('event-description').value.trim();
        const start = document.getElementById('event-start').value;
        const end = document.getElementById('event-end').value;
        const location = document.getElementById('event-location').value.trim();
        const max = parseInt(document.getElementById('event-max').value) || null;
        const points = parseInt(document.getElementById('event-points').value) || 0;
        const requiresConfirmation = document.getElementById('event-requires-confirmation').checked;
        const requiresDocuments = document.getElementById('event-requires-documents').checked;

        if (!title || !start) {
            showToast('Название и дата начала обязательны', 'warning');
            return;
        }

        const payload = {
            title,
            description: description || null,
            start_datetime: start,
            end_datetime: end || null,
            location: location || null,
            max_participants: max,
            points,
            requires_confirmation: requiresConfirmation,
            requires_documents: requiresDocuments,
            // class_ids будут добавлены на бэке автоматически для учителя
        };

        showLoading(true);
        try {
            if (eventId) {
                await apiCall('event.update', { event_id: eventId, ...payload });
                showToast('Мероприятие обновлено', 'success');
            } else {
                await apiCall('event.create', payload);
                showToast('Мероприятие создано', 'success');
            }
            bootstrap.Modal.getInstance(document.getElementById('create-event-modal')).hide();
            document.getElementById('event-form').reset();
            document.getElementById('event-id').value = '';
            await loadEvents();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Редактирование
    document.getElementById('events-list').addEventListener('click', function(e) {
        const target = e.target.closest('.edit-event');
        if (target) {
            const eventId = target.dataset.id;
            const event = events.find(e => e.id == eventId);
            if (event) {
                document.getElementById('event-id').value = event.id;
                document.getElementById('event-title').value = event.title;
                document.getElementById('event-description').value = event.description || '';
                document.getElementById('event-start').value = event.start_datetime.slice(0, 16);
                document.getElementById('event-end').value = event.end_datetime ? event.end_datetime.slice(0, 16) : '';
                document.getElementById('event-location').value = event.location || '';
                document.getElementById('event-max').value = event.max_participants || '';
                document.getElementById('event-points').value = event.points || 0;
                document.getElementById('event-requires-confirmation').checked = !!event.requires_confirmation;
                document.getElementById('event-requires-documents').checked = !!event.requires_documents;
                document.getElementById('event-modal-title').textContent = 'Редактировать мероприятие';
                const modal = new bootstrap.Modal(document.getElementById('create-event-modal'));
                modal.show();
            }
        }
    });

    // Отмена
    document.getElementById('events-list').addEventListener('click', async function(e) {
        const target = e.target.closest('.cancel-event');
        if (target) {
            const eventId = target.dataset.id;
            if (!confirm('Отменить мероприятие?')) return;
            showLoading(true);
            try {
                await apiCall('event.delete', { event_id: eventId });
                showToast('Мероприятие отменено', 'success');
                await loadEvents();
            } catch (e) {
                showToast(e.message, 'danger');
            } finally {
                showLoading(false);
            }
        }
    });

    // При открытии модалки создания – сброс
    document.getElementById('create-event-modal').addEventListener('show.bs.modal', function() {
        if (!document.getElementById('event-id').value) {
            document.getElementById('event-modal-title').textContent = 'Создать мероприятие';
            document.getElementById('event-form').reset();
        }
    });

    // При закрытии модалки – сброс
    document.getElementById('create-event-modal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('event-id').value = '';
        document.getElementById('event-form').reset();
    });
}
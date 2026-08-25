/**
 * Детальная страница мероприятия.
 */
let eventData = null;
let children = [];
let registrations = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    const eventId = window.eventId;
    if (!eventId) {
        document.getElementById('eventDetail').innerHTML = '<div class="alert alert-danger">ID мероприятия не указан.</div>';
        return;
    }
    showLoading(true);
    try {
        // Загружаем данные мероприятия
        eventData = await apiCall('event.get', { event_id: eventId });
        // Загружаем детей родителя
        children = await apiCall('parent.getChildren');
        // Загружаем регистрации на это мероприятие (используем расширенный метод)
        try {
            registrations = await apiCall('parent.getChildrenEvents', { event_id: eventId });
        } catch {
            registrations = [];
        }
        renderEventDetail();
    } catch (e) {
        document.getElementById('eventDetail').innerHTML = `<div class="alert alert-danger">${e.message}</div>`;
    } finally {
        showLoading(false);
    }
}

function renderEventDetail() {
    const container = document.getElementById('eventDetail');
    if (!eventData) return;

    // Основная информация
    let html = `
        <h3>${eventData.title}</h3>
        <p class="text-muted">${eventData.description || ''}</p>
        <ul class="list-unstyled">
            <li><i class="bi bi-calendar-event"></i> ${new Date(eventData.start_datetime).toLocaleString()}</li>
            ${eventData.end_datetime ? `<li><i class="bi bi-calendar-range"></i> до ${new Date(eventData.end_datetime).toLocaleString()}</li>` : ''}
            <li><i class="bi bi-geo-alt"></i> ${eventData.location || 'Место не указано'}</li>
            <li><i class="bi bi-tag"></i> ${eventData.category_name || 'Без категории'}</li>
            ${eventData.tags && eventData.tags.length ? `<li><i class="bi bi-hash"></i> ${eventData.tags.map(t => t.name).join(', ')}</li>` : ''}
        </ul>
        <hr>
    `;

    // Блок для каждого ребёнка – запись/отмена
    if (children && children.length) {
        html += `<h5>Запись детей</h5><div class="row">`;
        children.forEach(child => {
            const reg = registrations.find(r => r.student_id == child.id);
            const status = reg ? reg.status : null;
            let buttonHtml = '';
            if (status === 'approved' || status === 'pending') {
                buttonHtml = `<button class="btn btn-sm btn-danger unregister-btn" data-child="${child.id}">Отменить запись</button>`;
            } else {
                buttonHtml = `<button class="btn btn-sm btn-primary register-btn" data-child="${child.id}">Записаться</button>`;
            }
            html += `
                <div class="col-md-6 mb-2">
                    <div class="d-flex justify-content-between align-items-center border p-2 rounded">
                        <span>${child.full_name}</span>
                        <span>${status ? `<span class="badge bg-${status === 'approved' ? 'success' : status === 'pending' ? 'warning' : 'secondary'}">${status}</span>` : 'не записан'}</span>
                        ${buttonHtml}
                    </div>
                </div>
            `;
        });
        html += `</div><hr>`;
    }

    // Блок согласий (заглушка)
    if (eventData.requires_documents) {
        html += `
            <h5>📝 Согласия</h5>
            <p class="text-muted">Для участия необходимо загрузить согласие.</p>
            <button class="btn btn-outline-primary" onclick="showToast('Форма загрузки согласия (заглушка)', 'info')">Загрузить согласие</button>
            <hr>
        `;
    }

    // Блок документов (заглушка)
    html += `
        <h5>📄 Документы мероприятия</h5>
        <p class="text-muted">Список документов, загруженных участниками (заглушка).</p>
    `;

    container.innerHTML = html;

    // Обработчики для кнопок записи/отмены
    container.querySelectorAll('.register-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const childId = this.dataset.child;
            await registerChild(childId);
        });
    });
    container.querySelectorAll('.unregister-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const childId = this.dataset.child;
            await unregisterChild(childId);
        });
    });
}

async function registerChild(childId) {
    showLoading(true);
    try {
        await apiCall('parent.registerChildForEvent', {
            student_id: childId,
            event_id: eventData.id
        });
        showToast('Запись успешна', 'success');
        // Обновляем страницу
        await initPage();
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

async function unregisterChild(childId) {
    showLoading(true);
    try {
        await apiCall('parent.unregisterChildForEvent', {
            student_id: childId,
            event_id: eventData.id
        });
        showToast('Запись отменена', 'success');
        await initPage();
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}
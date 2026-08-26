let events = [];
let categories = [];
let classes = [];
let tags = [];
let currentFilters = { dateFrom: '', dateTo: '', status: '', category: '' };
let isEditMode = false;

/**
 * Загружает справочники: категории, классы, теги.
 */
async function loadDictionaries() {
    try {
        [categories, classes, tags] = await Promise.all([
            apiCall('admin.categoryList'),
            apiCall('admin.classList'),
            apiCall('admin.tagList')
        ]);
        // Заполняем select в фильтре категорий
        const filterCategory = document.getElementById('filter-category');
        filterCategory.innerHTML = '<option value="">Все категории</option>' +
            categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        // Заполняем select в форме
        const formCategory = document.getElementById('event-category');
        formCategory.innerHTML = '<option value="">Выберите</option>' +
            categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        const classSelect = document.getElementById('event-classes');
        classSelect.innerHTML = classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        const tagSelect = document.getElementById('event-tags');
        tagSelect.innerHTML = tags.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
    } catch (error) {
        console.warn('Не удалось загрузить справочники', error);
        showToast('Ошибка загрузки справочников', 'warning');
    }
}

/**
 * Загружает список мероприятий с учётом фильтров.
 */
async function loadEvents() {
    showLoading(true);
    try {
        const params = {};
        if (currentFilters.dateFrom) params.start_date = currentFilters.dateFrom;
        if (currentFilters.dateTo) params.end_date = currentFilters.dateTo;
        if (currentFilters.status) params.status = currentFilters.status;
        if (currentFilters.category) params.category_id = currentFilters.category;
        events = await apiCall('event.list', params);
        renderEvents();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Рендерит таблицу мероприятий.
 */
function renderEvents() {
    const tbody = document.getElementById('events-body');
    if (events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Нет мероприятий</td></tr>';
        return;
    }

    const now = Date.now();
    tbody.innerHTML = events.map(ev => {
        const startDate = new Date(ev.start_datetime).getTime();
        const canEdit = startDate > now;
        return `
        <tr>
            <td>${ev.title}</td>
            <td>${new Date(ev.start_datetime).toLocaleString()}</td>
            <td>${ev.location || ''}</td>
            <td>${ev.category_name || ''}</td>
            <td>${ev.current_count || 0}${ev.max_participants ? '/' + ev.max_participants : ''}</td>
            <td>${ev.points || 0}</td>
            <td><span class="badge bg-${ev.status === 'active' ? 'success' : ev.status === 'cancelled' ? 'danger' : 'secondary'}">${ev.status}</span></td>
            <td>
                ${canEdit ? `<button class="btn btn-sm btn-primary edit-event" data-id="${ev.id}">Редактировать</button>` : ''}
                ${ev.status === 'active' ? `<button class="btn btn-sm btn-danger cancel-event" data-id="${ev.id}">Отменить</button>` : ''}
            </td>
        </tr>
    `}).join('');

    // Обработчики
    document.querySelectorAll('.edit-event').forEach(btn => {
        btn.addEventListener('click', function() {
            openEditModal(this.dataset.id);
        });
    });
    document.querySelectorAll('.cancel-event').forEach(btn => {
        btn.addEventListener('click', function() {
            cancelEvent(this.dataset.id);
        });
    });
}

/**
 * Открывает модалку для создания мероприятия.
 */
function openCreateModal() {
    isEditMode = false;
    document.getElementById('edit-event-id').value = '';
    document.getElementById('eventModalLabel').textContent = 'Создать мероприятие';
    document.getElementById('event-form').reset();
    document.getElementById('event-requires-confirmation').checked = true;
    document.getElementById('event-requires-documents').checked = false;
    document.getElementById('event-dormitory-true').checked = true;
    document.getElementById('event-dormitory-false').checked = true;
    document.getElementById('event-classes').selectedIndex = -1;
    document.getElementById('event-tags').selectedIndex = -1;
    document.getElementById('event-category').value = '';
    $('#eventModal').modal('show');
}

/**
 * Открывает модалку для редактирования мероприятия.
 */
async function openEditModal(eventId) {
    isEditMode = true;
    showLoading(true);
    try {
        const eventData = await apiCall('event.get', { event_id: eventId });
        document.getElementById('edit-event-id').value = eventId;
        document.getElementById('eventModalLabel').textContent = 'Редактировать мероприятие';
        document.getElementById('event-title').value = eventData.title || '';
        document.getElementById('event-description').value = eventData.description || '';
        // Преобразуем даты для datetime-local
        const start = eventData.start_datetime ? eventData.start_datetime.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('event-start').value = start;
        const end = eventData.end_datetime ? eventData.end_datetime.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('event-end').value = end;
        document.getElementById('event-location').value = eventData.location || '';
        document.getElementById('event-category').value = eventData.category_id || '';
        document.getElementById('event-max-participants').value = eventData.max_participants || '';
        document.getElementById('event-points').value = eventData.points || 0;
        document.getElementById('event-requires-confirmation').checked = eventData.requires_confirmation !== undefined ? eventData.requires_confirmation : true;
        document.getElementById('event-requires-documents').checked = eventData.requires_documents || false;

        // Выбор классов
        const classSelect = document.getElementById('event-classes');
        const classIds = eventData.class_access ? eventData.class_access.map(c => c.id) : [];
        Array.from(classSelect.options).forEach(opt => {
            opt.selected = classIds.includes(parseInt(opt.value));
        });

        // Выбор тегов
        const tagSelect = document.getElementById('event-tags');
        const tagIds = eventData.tags ? eventData.tags.map(t => t.id) : [];
        Array.from(tagSelect.options).forEach(opt => {
            opt.selected = tagIds.includes(parseInt(opt.value));
        });

        // Доступность по проживанию
        const dormAccess = eventData.dormitory_access || [];
        document.getElementById('event-dormitory-true').checked = dormAccess.includes(true);
        document.getElementById('event-dormitory-false').checked = dormAccess.includes(false);

        $('#eventModal').modal('show');
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Сохраняет мероприятие (создание или обновление).
 */
async function saveEvent() {
    const title = document.getElementById('event-title').value.trim();
    const start = document.getElementById('event-start').value;
    if (!title || !start) {
        showToast('Заполните обязательные поля (название и дата начала)', 'warning');
        return;
    }

    // Преобразуем datetime-local в формат YYYY-MM-DD HH:MM:SS
    const formatDateTime = (val) => {
        if (!val) return null;
        return val.replace('T', ' ') + ':00';
    };

    const data = {
        title: title,
        description: document.getElementById('event-description').value.trim(),
        start_datetime: formatDateTime(start),
        end_datetime: formatDateTime(document.getElementById('event-end').value),
        location: document.getElementById('event-location').value.trim(),
        category_id: document.getElementById('event-category').value || null,
        max_participants: parseInt(document.getElementById('event-max-participants').value) || null,
        points: parseInt(document.getElementById('event-points').value) || 0,
        requires_confirmation: document.getElementById('event-requires-confirmation').checked,
        requires_documents: document.getElementById('event-requires-documents').checked,
        class_ids: Array.from(document.getElementById('event-classes').selectedOptions).map(opt => parseInt(opt.value)),
        tag_ids: Array.from(document.getElementById('event-tags').selectedOptions).map(opt => parseInt(opt.value)),
        dormitory_access: []
    };

    if (document.getElementById('event-dormitory-true').checked) data.dormitory_access.push(true);
    if (document.getElementById('event-dormitory-false').checked) data.dormitory_access.push(false);

    const eventId = document.getElementById('edit-event-id').value;
    const method = isEditMode ? 'event.update' : 'event.create';
    if (isEditMode) data.event_id = parseInt(eventId);

    showLoading(true);
    try {
        await apiCall(method, data);
        showToast(isEditMode ? 'Мероприятие обновлено' : 'Мероприятие создано', 'success');
        $('#eventModal').modal('hide');
        await loadEvents();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Отмена мероприятия (перевод в статус cancelled).
 */
async function cancelEvent(eventId) {
    if (!confirm('Вы уверены, что хотите отменить это мероприятие?')) return;
    showLoading(true);
    try {
        await apiCall('event.delete', { event_id: eventId });
        showToast('Мероприятие отменено', 'success');
        await loadEvents();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Инициализация страницы.
 */
function initPage() {
    // Загружаем справочники
    loadDictionaries();

    // Фильтры
    document.getElementById('filter-date-from').addEventListener('change', function() {
        currentFilters.dateFrom = this.value;
        loadEvents();
    });
    document.getElementById('filter-date-to').addEventListener('change', function() {
        currentFilters.dateTo = this.value;
        loadEvents();
    });
    document.getElementById('filter-status').addEventListener('change', function() {
        currentFilters.status = this.value;
        loadEvents();
    });
    document.getElementById('filter-category').addEventListener('change', function() {
        currentFilters.category = this.value;
        loadEvents();
    });
    document.getElementById('clear-filters').addEventListener('click', function() {
        document.getElementById('filter-date-from').value = '';
        document.getElementById('filter-date-to').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-category').value = '';
        currentFilters = { dateFrom: '', dateTo: '', status: '', category: '' };
        loadEvents();
    });

    // Кнопка создания
    document.getElementById('create-event-btn').addEventListener('click', openCreateModal);

    // Кнопка сохранения в модалке
    document.getElementById('save-event-btn').addEventListener('click', saveEvent);

    // Загружаем список мероприятий
    loadEvents();
}
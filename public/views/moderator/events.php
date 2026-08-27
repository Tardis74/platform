<?php
// Определяем необходимое разрешение для данной страницы
$requiredPermission = 'events.view'; // заменить на конкретное разрешение

// Проверяем, если роль custom и нет нужного разрешения – запрещаем доступ
if ($_SESSION['role'] === 'custom' && !in_array($requiredPermission, $_SESSION['permissions'] ?? [])) {
    echo '<div class="alert alert-danger">Доступ запрещён. <a href="/custom/dashboard">На главную</a></div>';
    return; // прекращаем выполнение шаблона
}
?>
<h1>Управление мероприятиями</h1>

<div class="mb-3">
    <button class="btn btn-primary" id="create-event-btn">Создать мероприятие</button>
</div>

<div class="row mb-3">
    <div class="col-md-2">
        <input type="date" class="form-control" id="filter-date-from" placeholder="Дата от">
    </div>
    <div class="col-md-2">
        <input type="date" class="form-control" id="filter-date-to" placeholder="Дата до">
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filter-status">
            <option value="">Все статусы</option>
            <option value="active">Активные</option>
            <option value="cancelled">Отменённые</option>
            <option value="completed">Завершённые</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="filter-category">
            <option value="">Все категории</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-secondary" id="clear-filters">Сбросить</button>
    </div>
</div>

<table class="table table-striped" id="events-table">
    <thead>
        <tr>
            <th>Название</th>
            <th>Дата/время</th>
            <th>Место</th>
            <th>Категория</th>
            <th>Участники</th>
            <th>Баллы</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody id="events-body">
        <tr><td colspan="8" class="text-center">Загрузка...</td></tr>
    </tbody>
</table>

<!-- Модальное окно создания/редактирования -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Создать мероприятие</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="event-form">
                    <input type="hidden" id="edit-event-id" value="">
                    <div class="mb-3">
                        <label for="event-title" class="form-label">Название *</label>
                        <input type="text" class="form-control" id="event-title" required>
                    </div>
                    <div class="mb-3">
                        <label for="event-description" class="form-label">Описание</label>
                        <textarea class="form-control" id="event-description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="event-start" class="form-label">Дата и время начала *</label>
                        <input type="datetime-local" class="form-control" id="event-start" required>
                    </div>
                    <div class="mb-3">
                        <label for="event-end" class="form-label">Дата и время окончания</label>
                        <input type="datetime-local" class="form-control" id="event-end">
                    </div>
                    <div class="mb-3">
                        <label for="event-location" class="form-label">Место</label>
                        <input type="text" class="form-control" id="event-location">
                    </div>
                    <div class="mb-3">
                        <label for="event-category" class="form-label">Категория</label>
                        <select class="form-select" id="event-category">
                            <option value="">Выберите</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="event-max-participants" class="form-label">Максимальное количество участников</label>
                        <input type="number" class="form-control" id="event-max-participants" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="event-points" class="form-label">Баллы за участие</label>
                        <input type="number" class="form-control" id="event-points" value="0" min="0">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="event-requires-confirmation" checked>
                        <label class="form-check-label" for="event-requires-confirmation">Требуется подтверждение модератором</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="event-requires-documents">
                        <label class="form-check-label" for="event-requires-documents">Требуется загрузка документов</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Доступность по классам</label>
                        <select class="form-select" id="event-classes" multiple>
                            <!-- опции загружаются через JS -->
                        </select>
                        <small class="text-muted">Удерживайте Ctrl для множественного выбора</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Доступность по типу проживания</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="event-dormitory-true" checked>
                            <label class="form-check-label" for="event-dormitory-true">Для проживающих в общежитии</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="event-dormitory-false" checked>
                            <label class="form-check-label" for="event-dormitory-false">Для горожан</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Теги</label>
                        <select class="form-select" id="event-tags" multiple>
                            <!-- опции загружаются через JS -->
                        </select>
                        <small class="text-muted">Удерживайте Ctrl для множественного выбора</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-event-btn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/moderator-events.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initPage === 'function') initPage();
    });
</script>
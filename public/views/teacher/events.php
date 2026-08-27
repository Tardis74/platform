<?php
// Определяем необходимое разрешение для данной страницы
$requiredPermission = 'events.view'; // заменить на конкретное разрешение

// Проверяем, если роль custom и нет нужного разрешения – запрещаем доступ
if ($_SESSION['role'] === 'custom' && !in_array($requiredPermission, $_SESSION['permissions'] ?? [])) {
    echo '<div class="alert alert-danger">Доступ запрещён. <a href="/custom/dashboard">На главную</a></div>';
    return; // прекращаем выполнение шаблона
}
?>
<?php
$pageTitle = 'Мероприятия класса';
$pageScript = '/assets/js/teacher-events.js';
?>
<div class="container mt-4">
    <h1 class="mb-4">Мероприятия класса</h1>
    <div class="d-flex justify-content-between mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create-event-modal">Создать мероприятие</button>
    </div>
    <div id="events-list">
        <p class="text-muted">Загрузка...</p>
    </div>
</div>

<!-- Модальное окно создания/редактирования мероприятия -->
<div class="modal fade" id="create-event-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="event-modal-title">Создать мероприятие</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="event-form">
                    <input type="hidden" id="event-id">
                    <div class="mb-3">
                        <label for="event-title" class="form-label">Название *</label>
                        <input type="text" class="form-control" id="event-title" required>
                    </div>
                    <div class="mb-3">
                        <label for="event-description" class="form-label">Описание</label>
                        <textarea class="form-control" id="event-description" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="event-start" class="form-label">Дата/время начала *</label>
                            <input type="datetime-local" class="form-control" id="event-start" required>
                        </div>
                        <div class="col-md-6">
                            <label for="event-end" class="form-label">Дата/время окончания</label>
                            <input type="datetime-local" class="form-control" id="event-end">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="event-location" class="form-label">Место</label>
                        <input type="text" class="form-control" id="event-location">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="event-max" class="form-label">Максимум участников</label>
                            <input type="number" class="form-control" id="event-max" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="event-points" class="form-label">Баллы</label>
                            <input type="number" class="form-control" id="event-points" min="0" value="0">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="event-requires-confirmation">
                        <label class="form-check-label" for="event-requires-confirmation">Требуется подтверждение</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="event-requires-documents">
                        <label class="form-check-label" for="event-requires-documents">Требуются документы</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-event">Сохранить</button>
            </div>
        </div>
    </div>
</div>
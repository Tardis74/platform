<?php
// Определяем необходимое разрешение для данной страницы
$requiredPermission = 'achievements.view'; // заменить на конкретное разрешение

// Проверяем, если роль custom и нет нужного разрешения – запрещаем доступ
if ($_SESSION['role'] === 'custom' && !in_array($requiredPermission, $_SESSION['permissions'] ?? [])) {
    echo '<div class="alert alert-danger">Доступ запрещён. <a href="/custom/dashboard">На главную</a></div>';
    return; // прекращаем выполнение шаблона
}
?>
<h1>Проверка достижений</h1>

<div class="row mb-3">
    <div class="col-md-3">
        <input type="text" class="form-control" id="filter-student" placeholder="Фильтр по ученику">
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

<table class="table table-striped" id="achievements-table">
    <thead>
        <tr>
            <th>Ученик</th>
            <th>Класс</th>
            <th>Категория</th>
            <th>Название</th>
            <th>Дата загрузки</th>
            <th>Файл</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody id="achievements-body">
        <tr><td colspan="7" class="text-center">Загрузка...</td></tr>
    </tbody>
</table>

<!-- Модальное окно отклонения -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Отклонить достижение</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject-achievement-id">
                <div class="mb-3">
                    <label for="reject-comment" class="form-label">Комментарий (причина отклонения)</label>
                    <textarea class="form-control" id="reject-comment" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirm-reject">Отклонить</button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/moderator-achievements.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initPage === 'function') initPage();
    });
</script>
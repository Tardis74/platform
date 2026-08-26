<?php
// views/admin/audit.php
?>
<h1>Журнал аудита</h1>
<div class="row mb-3">
    <div class="col-md-3"><input type="date" class="form-control" id="auditDateFrom"></div>
    <div class="col-md-3"><input type="date" class="form-control" id="auditDateTo"></div>
    <div class="col-md-3">
        <input type="text" class="form-control" id="auditUser" placeholder="Пользователь (ФИО)">
    </div>
    <div class="col-md-3">
        <select class="form-select" id="auditEventType">
            <option value="">Все события</option>
            <option value="login">Вход</option>
            <option value="logout">Выход</option>
            <option value="user_create">Создание пользователя</option>
            <option value="user_update">Обновление пользователя</option>
            <option value="user_delete">Удаление пользователя</option>
            <option value="role_change">Смена роли</option>
            <option value="permission_change">Смена прав</option>
            <option value="class_create">Создание класса</option>
            <option value="class_update">Обновление класса</option>
            <option value="class_archive">Архивация класса</option>
            <option value="tag_create">Создание тега</option>
            <option value="tag_update">Обновление тега</option>
            <option value="tag_delete">Удаление тега</option>
            <option value="template_create">Создание шаблона</option>
            <option value="template_update">Обновление шаблона</option>
            <option value="template_delete">Удаление шаблона</option>
            <option value="category_create">Создание категории</option>
            <option value="category_update">Обновление категории</option>
            <option value="category_delete">Удаление категории</option>
            <option value="report_generate">Генерация отчёта</option>
            <option value="report_download">Скачивание отчёта</option>
            <option value="rating_publish">Публикация рейтинга</option>
            <option value="rating_unpublish">Снятие рейтинга</option>
        </select>
    </div>
</div>
<button class="btn btn-secondary mb-3" id="auditExport">Экспорт CSV</button>
<table class="table table-striped" id="audit-table">
    <thead><tr><th>Дата/время</th><th>Пользователь</th><th>IP</th><th>Тип события</th><th>Объект изменения</th></tr></thead>
    <tbody id="audit-tbody"></tbody>
</table>
<nav><ul class="pagination" id="audit-pagination"></ul></nav>
<p class="text-muted">Срок хранения: не менее 1 года. Архивация производится автоматически.</p>
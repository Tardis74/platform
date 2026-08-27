<?php
// views/admin/users.php
?>
<h1 class="mb-4">Управление пользователями</h1>
<div class="d-flex justify-content-between mb-3">
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">Создать пользователя</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importUserModal">Импорт CSV</button>
    </div>
    <div class="input-group" style="width:300px;">
        <input type="text" class="form-control" id="userSearch" placeholder="Поиск по ФИО/email">
        <button class="btn btn-outline-secondary" id="searchBtn">Найти</button>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-4">
        <select class="form-select" id="filterRole">
            <option value="">Все роли</option>
            <option value="admin">Администратор</option>
            <option value="teacher">Учитель</option>
            <option value="parent">Родитель</option>
            <option value="student">Ученик</option>
            <option value="moderator">Модератор</option>
        </select>
    </div>
    <div class="col-md-4">
        <select class="form-select" id="filterStatus">
            <option value="">Все статусы</option>
            <option value="active">Активен</option>
            <option value="blocked">Заблокирован</option>
        </select>
    </div>
</div>
<table class="table table-striped" id="users-table">
    <thead><tr><th data-sort="id">ID</th><th data-sort="full_name">ФИО</th><th data-sort="email">Email</th><th data-sort="role">Роль</th><th data-sort="created_at">Дата регистрации</th><th data-sort="status">Статус</th><th>Действия</th></tr></thead>
    <tbody id="users-tbody"></tbody>
</table>
<nav><ul class="pagination" id="users-pagination"></ul></nav>

<!-- Модалка создания -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Создать пользователя</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="createUserForm">
                <div class="mb-3"><label>ФИО</label><input type="text" class="form-control" name="full_name" required></div>
                <div class="mb-3"><label>Email</label><input type="email" class="form-control" name="email" required></div>
                <div class="mb-3"><label>Пароль</label><input type="password" class="form-control" name="password" required></div>
                <div class="mb-3"><label>Роль</label>
                    <select class="form-select" name="role">
                        <option value="student">Ученик</option>
                        <option value="teacher">Учитель</option>
                        <option value="parent">Родитель</option>
                        <option value="moderator">Модератор</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Создать</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка редактирования -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Редактировать пользователя</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="editUserForm">
                <input type="hidden" name="id">
                <div class="mb-3"><label>ФИО</label><input type="text" class="form-control" name="full_name" required></div>
                <div class="mb-3"><label>Email</label><input type="email" class="form-control" name="email" required></div>
                <div class="mb-3"><label>Роль</label>
                    <select class="form-select" name="role">
                        <option value="student">Ученик</option>
                        <option value="teacher">Учитель</option>
                        <option value="parent">Родитель</option>
                        <option value="moderator">Модератор</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="reset_password" value="1">
                        <label class="form-check-label">Сбросить пароль (новый будет сгенерирован)</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка импорта -->
<div class="modal fade" id="importUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5>Импорт пользователей из CSV</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="importUserForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label>Файл CSV (разделитель ;, первая строка – заголовки)</label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary">Загрузить и предпросмотр</button>
            </form>
            <div id="importPreview" style="display:none; margin-top:20px;">
                <h6>Предпросмотр данных</h6>
                <div id="importTableContainer"></div>
                <button class="btn btn-success" id="confirmImport">Подтвердить импорт</button>
            </div>
        </div>
    </div></div>
</div>

<script src="/assets/js/admin-users.js"></script>
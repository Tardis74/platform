<?php
// views/admin/permissions.php
?>
<h1>Управление правами доступа</h1>
<div class="row">
    <div class="col-md-4">
        <label>Выберите пользователя</label>
        <input type="text" class="form-control" id="permUserSearch" placeholder="Поиск по ФИО/email">
        <div id="userSuggestions" class="list-group mt-1"></div>
        <input type="hidden" id="selectedUserId">
    </div>
    <div class="col-md-8">
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="permMode" value="role" checked>
                <label class="form-check-label">Стандартная роль</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="permMode" value="custom">
                <label class="form-check-label">Персональные права</label>
            </div>
        </div>
        <div id="roleMode">
            <select class="form-select" id="roleSelect">
                <option value="admin">Администратор</option>
                <option value="teacher">Учитель</option>
                <option value="parent">Родитель</option>
                <option value="student">Ученик</option>
                <option value="moderator">Модератор</option>
            </select>
            <button class="btn btn-primary mt-2" id="applyRole">Применить</button>
        </div>
        <div id="customMode" style="display:none;">
            <div class="mb-2"><input type="text" class="form-control" id="permSearch" placeholder="Поиск по названию разрешения"></div>
            <div id="permissionsTree"></div>
            <button class="btn btn-primary mt-2" id="savePermissions">Сохранить</button>
        </div>
    </div>
</div>
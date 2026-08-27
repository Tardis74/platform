<h1>Управление правами доступа</h1>
<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label>Выберите пользователя</label>
            <input type="text" class="form-control" id="permUserSearch" placeholder="Поиск по ФИО/email">
            <div id="userSuggestions" class="list-group mt-1"></div>
            <input type="hidden" id="selectedUserId">
        </div>
        <div id="currentUserInfo" style="display:none;">
            <p><strong>Текущие права:</strong></p>
            <div id="currentRightsDisplay"></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="permMode" value="standard" checked>
                <label class="form-check-label">Стандартная роль</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="permMode" value="custom">
                <label class="form-check-label">Персональные права</label>
            </div>
        </div>

        <div id="standardMode">
            <select class="form-select" id="roleSelect">
                <option value="admin">Администратор</option>
                <option value="moderator">Модератор</option>
                <option value="teacher">Учитель</option>
                <option value="parent">Родитель</option>
                <option value="student">Ученик</option>
                <option value="canteen">Питание</option>
                <option value="educator">Воспитатель</option>
                <option value="kpp">КПП</option>
            </select>
            <button class="btn btn-primary mt-2" id="applyRole">Применить</button>
        </div>

        <div id="customMode" style="display:none;">
            <div class="mb-2">
                <input type="text" class="form-control" id="permSearch" placeholder="Поиск по названию разрешения">
            </div>
            <div id="permissionsTree"></div>
            <button class="btn btn-primary mt-2" id="savePermissions">Сохранить</button>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin-permissions.js"></script>
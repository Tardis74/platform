<?php
// views/admin/tags.php
?>
<h1>Управление тегами</h1>
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTagModal">Создать тег</button>
    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#assignTagModal">Назначить теги ученикам</button>
    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#massAssignModal">Массовое назначение по классу</button>
</div>
<table class="table table-striped" id="tags-table">
    <thead><tr><th>ID</th><th>Название</th><th>Действия</th></tr></thead>
    <tbody id="tags-tbody"></tbody>
</table>

<!-- Модалка создания тега -->
<div class="modal fade" id="createTagModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Создать тег</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="createTagForm">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <button type="submit" class="btn btn-primary">Создать</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка редактирования тега -->
<div class="modal fade" id="editTagModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Редактировать тег</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="editTagForm">
                <input type="hidden" name="id">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка назначения тегов ученику -->
<div class="modal fade" id="assignTagModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Назначить теги ученику</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="assignTagForm">
                <div class="mb-3"><label>Ученик</label>
                    <input type="text" class="form-control" id="assignStudentSearch" placeholder="Поиск по ФИО/классу">
                    <div id="assignStudentSuggestions" class="list-group mt-1"></div>
                    <input type="hidden" name="student_id">
                </div>
                <div class="mb-3"><label>Теги</label>
                    <div id="assignTagCheckboxes"></div>
                </div>
                <button type="submit" class="btn btn-primary">Назначить</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка массового назначения -->
<div class="modal fade" id="massAssignModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Массовое назначение тегов классу</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="massAssignForm">
                <div class="mb-3"><label>Класс</label>
                    <select class="form-select" name="class_id" required></select>
                </div>
                <div class="mb-3"><label>Теги</label>
                    <div id="massAssignTagCheckboxes"></div>
                </div>
                <button type="submit" class="btn btn-primary">Назначить всем</button>
            </form>
        </div>
    </div></div>
</div>
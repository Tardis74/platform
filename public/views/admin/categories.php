<?php
// views/admin/categories.php
?>
<h1>Управление категориями достижений</h1>
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">Создать категорию</button>
</div>
<table class="table table-striped" id="categories-table">
    <thead><tr><th>Название</th><th>Вес (баллы)</th><th>Действия</th></tr></thead>
    <tbody id="categories-tbody"></tbody>
</table>

<!-- Модалка создания -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Создать категорию</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="createCategoryForm">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label>Вес (баллы)</label><input type="number" class="form-control" name="weight" value="1" required></div>
                <button type="submit" class="btn btn-primary">Создать</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка редактирования -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Редактировать категорию</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="editCategoryForm">
                <input type="hidden" name="id">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label>Вес (баллы)</label><input type="number" class="form-control" name="weight" required></div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div></div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin-categories.js"></script>
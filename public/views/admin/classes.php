<?php
// views/admin/classes.php
?>
<h1 class="mb-4">Управление классами и учебными годами</h1>

<h5>Учебные годы</h5>
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createYearModal">Создать учебный год</button>
    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#transferModal">Перевести учеников</button>
</div>
<table class="table table-sm" id="years-table">
    <thead><tr><th>Название</th><th>Дата начала</th><th>Дата окончания</th><th>Статус</th><th>Действия</th></tr></thead>
    <tbody id="years-tbody"></tbody>
</table>

<h5 class="mt-4">Классы</h5>
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">Создать класс</button>
</div>
<table class="table table-striped" id="classes-table">
    <thead><tr><th>Название</th><th>Учебный год</th><th>Классный руководитель</th><th>Кол-во учеников</th><th>Действия</th></tr></thead>
    <tbody id="classes-tbody"></tbody>
</table>

<!-- Модалка создания учебного года -->
<div class="modal fade" id="createYearModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Создать учебный год</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="createYearForm">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label>Дата начала</label><input type="date" class="form-control" name="start_date" required></div>
                <div class="mb-3"><label>Дата окончания</label><input type="date" class="form-control" name="end_date" required></div>
                <button type="submit" class="btn btn-primary">Создать</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка создания класса -->
<div class="modal fade" id="createClassModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Создать класс</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="createClassForm">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label>Учебный год</label>
                    <select class="form-select" name="academic_year_id" required></select>
                </div>
                <div class="mb-3"><label>Классный руководитель</label>
                    <select class="form-select" name="teacher_id"></select>
                </div>
                <button type="submit" class="btn btn-primary">Создать</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка перевода учеников -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Перевести учеников</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="transferForm">
                <div class="mb-3"><label>Класс-источник</label>
                    <select class="form-select" name="from_class_id" required></select>
                </div>
                <div class="mb-3"><label>Класс-назначение</label>
                    <select class="form-select" name="to_class_id" required></select>
                </div>
                <button type="submit" class="btn btn-warning">Перевести</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка просмотра учеников класса -->
<div class="modal fade" id="viewClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5>Ученики класса</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <table class="table" id="classStudentsTable"><tbody></tbody></table>
        </div>
    </div></div>
</div>

<!-- Модалка редактирования класса -->
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Редактировать класс</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="editClassForm">
                <input type="hidden" name="id">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label>Учебный год</label>
                    <select class="form-select" name="academic_year_id" required></select>
                </div>
                <div class="mb-3"><label>Классный руководитель</label>
                    <select class="form-select" name="teacher_id"></select>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div></div>
</div>

<!-- Модалка редактирования учебного года -->
<div class="modal fade" id="editYearModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5>Редактировать учебный год</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="editYearForm">
                <input type="hidden" name="id">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label>Дата начала</label><input type="date" class="form-control" name="start_date" required></div>
                <div class="mb-3"><label>Дата окончания</label><input type="date" class="form-control" name="end_date" required></div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="is_current" value="1" id="editYearCurrent">
                    <label class="form-check-label" for="editYearCurrent">Текущий год</label>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div></div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin-classes.js"></script>
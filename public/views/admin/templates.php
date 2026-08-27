<?php
// views/admin/templates.php
?>
<h1>Управление шаблонами документов</h1>
<div class="mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">Создать шаблон</button>
</div>
<table class="table table-striped" id="templates-table">
    <thead><tr><th>Название</th><th>Описание</th><th>Уровень подписи</th><th>Требует файл</th><th>Действия</th></tr></thead>
    <tbody id="templates-tbody"></tbody>
</table>

<!-- Модалка создания/редактирования -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 id="templateModalTitle">Создать шаблон</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="templateForm">
                <input type="hidden" name="id">
                <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label>Описание</label><textarea class="form-control" name="description"></textarea></div>
                <div class="mb-3"><label>Содержание (с плейсхолдерами {STUDENT_FIO}, {PARENT_FIO}, {CLASS}, {DATE}, {EVENT_NAME}, {EVENT_DATE})</label>
                    <textarea class="form-control" name="content" rows="6" required></textarea>
                </div>
                <div class="mb-3"><label>Уровень подписи</label>
                    <select class="form-select" name="signature_level">
                        <option value="simple">Простая</option>
                        <option value="sms">SMS</option>
                        <option value="goskey">ГосКлюч</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="requires_file" value="1" checked>
                        <label class="form-check-label">Требует загрузки файла</label>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" id="previewTemplate">Предпросмотр</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
            <div id="templatePreview" style="display:none; margin-top:20px; border:1px solid #ddd; padding:10px; background:#f9f9f9;"></div>
        </div>
    </div></div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin-templates.js"></script>
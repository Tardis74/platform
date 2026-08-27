<?php
// views/admin/rating.php
?>
<h1>Управление рейтингом</h1>
<div class="row">
    <div class="col-md-6">
        <h5>Просмотр портфолио ученика</h5>
        <div class="input-group mb-3">
            <input type="text" class="form-control" id="studentSearch" placeholder="Поиск по ФИО/классу">
            <button class="btn btn-outline-secondary" id="findStudent">Найти</button>
        </div>
        <div id="studentPortfolio"></div>
    </div>
    <div class="col-md-6">
        <h5>Публикация обезличенного рейтинга</h5>
        <div class="mb-3">
            <label>Период</label>
            <input type="month" class="form-control" id="ratingPeriod">
        </div>
        <div class="mb-3">
            <label>Классы</label>
            <select class="form-select" id="ratingClasses" multiple></select>
        </div>
        <div class="mb-3">
            <label>Категории</label>
            <select class="form-select" id="ratingCategories" multiple></select>
        </div>
        <button class="btn btn-success" id="buildRating">Сформировать рейтинг</button>
        <div id="ratingPreview" style="display:none; margin-top:20px;">
            <table class="table table-bordered" id="ratingTable">
                <thead><tr><th>Место</th><th>Идентификатор</th><th>Комментарий</th></tr></thead>
                <tbody id="ratingTbody"></tbody>
            </table>
            <button class="btn btn-primary" id="publishRating">Опубликовать</button>
            <button class="btn btn-secondary" id="unpublishRating">Снять публикацию</button>
        </div>
        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="showPlace">
            <label class="form-check-label">Показывать место в личном кабинете ученика</label>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin-rating.js"></script>
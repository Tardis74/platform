<?php
/**
 * Страница мероприятий – календарь/список с фильтрацией.
 */
?>
<div class="container-fluid py-4">
    <h4>📅 Мероприятия</h4>

    <!-- Фильтры -->
    <div class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label">Ребёнок</label>
            <select class="form-select" id="filterChild">
                <option value="">Все дети</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Статус записи</label>
            <select class="form-select" id="filterStatus">
                <option value="all">Все</option>
                <option value="pending">Ожидает</option>
                <option value="approved">Подтверждено</option>
                <option value="rejected">Отклонено</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Дата от</label>
            <input type="date" class="form-control" id="filterDateFrom">
        </div>
        <div class="col-md-3">
            <label class="form-label">Дата до</label>
            <input type="date" class="form-control" id="filterDateTo">
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" id="applyFilters">Применить</button>
        </div>
    </div>

    <!-- Список мероприятий -->
    <div id="eventsList" class="row row-cols-1 row-cols-md-2 g-3">
        <!-- Загружается через JS -->
    </div>
</div>

<script src="/assets/js/parent-events.js"></script>
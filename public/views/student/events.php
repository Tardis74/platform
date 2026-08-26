<?php
/**
 * Страница мероприятий (список доступных с фильтрацией).
 */
?>
<div id="student-events">
    <h2>Мероприятия</h2>

    <!-- Фильтры -->
    <div class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="filterDateFrom" class="form-label">Дата от</label>
            <input type="date" id="filterDateFrom" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="filterDateTo" class="form-label">Дата до</label>
            <input type="date" id="filterDateTo" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="filterCategory" class="form-label">Категория</label>
            <select id="filterCategory" class="form-select">
                <option value="">Все</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filterRegistrationStatus" class="form-label">Статус записи</label>
            <select id="filterRegistrationStatus" class="form-select">
                <option value="all">Все</option>
                <option value="available">Доступны для записи</option>
                <option value="registered">Я записан(а)</option>
            </select>
        </div>
        <div class="col-md-3">
            <button id="applyFilters" class="btn btn-primary w-100">Применить</button>
        </div>
    </div>

    <!-- Список мероприятий -->
    <div id="events-list" class="row row-cols-1 row-cols-md-2 g-4">
        <!-- Карточки будут вставлены JS -->
    </div>

    <!-- Пагинация -->
    <nav aria-label="Пагинация" class="mt-4">
        <ul class="pagination justify-content-center" id="pagination">
            <!-- будет добавлено JS -->
        </ul>
    </nav>
</div>

<script src="/assets/js/student-events.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initPage();
    });
</script>
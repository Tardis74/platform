<?php
/**
 * Дашборд ученика.
 * Данные загружаются через AJAX (student-dashboard.js).
 */
?>
<div id="student-dashboard">
    <!-- Приветствие -->
    <div id="greeting" class="mb-4">
        <h1 id="student-name">Загрузка...</h1>
        <p id="student-class" class="text-muted"></p>
    </div>

    <!-- Три колонки: календарь, заявления, портфолио -->
    <div class="row">
        <!-- Календарь мероприятий -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Мои мероприятия</h5>
                    <a href="/student/events" class="btn btn-sm btn-outline-primary">Все</a>
                </div>
                <div class="card-body" id="calendar-list">
                    <p class="text-muted">Загрузка...</p>
                </div>
            </div>
        </div>

        <!-- Активные заявления на выход (только для проживающих в общежитии) -->
        <div class="col-md-6 mb-4" id="leave-widget" style="display:none;">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Заявления на выход</h5>
                    <a href="/student/leave" class="btn btn-sm btn-outline-primary">Все</a>
                </div>
                <div class="card-body" id="leave-list">
                    <p class="text-muted">Загрузка...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Портфолио-виджет -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Портфолио</h5>
                    <a href="/student/portfolio" class="btn btn-sm btn-outline-primary">Перейти</a>
                </div>
                <div class="card-body" id="portfolio-widget">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Баллы: <strong id="total-points">0</strong></span>
                        <span>Место в рейтинге: <strong id="rating-place">—</strong></span>
                    </div>
                    <div id="recent-achievements">
                        <p class="text-muted">Нет достижений</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/student-dashboard.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initPage();
    });
</script>
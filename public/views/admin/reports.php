<?php
// views/admin/reports.php
?>
<h1>Отчёты</h1>
<div class="row">
    <div class="col-md-8">
        <form id="reportForm">
            <div class="mb-3"><label>Тип отчёта</label>
                <select class="form-select" id="reportType">
                    <option value="events">По мероприятиям</option>
                    <option value="city">По выходам в город</option>
                    <option value="portfolio">По портфолио</option>
                    <option value="documents">По документам</option>
                    <option value="meals">Табель питания</option>
                    <option value="consents">По согласиям</option>
                    <option value="classes">По классам</option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6"><label>Дата от</label><input type="date" class="form-control" id="dateFrom"></div>
                <div class="col-md-6"><label>Дата до</label><input type="date" class="form-control" id="dateTo"></div>
            </div>
            <div class="mb-3"><label>Классы (множественный выбор)</label>
                <select class="form-select" id="reportClasses" multiple></select>
            </div>
            <div class="mb-3"><label>Ученики</label>
                <select class="form-select" id="reportStudents" multiple></select>
            </div>
            <div class="mb-3" id="reportEventContainer" style="display:none;">
                <label>Мероприятия</label>
                <select class="form-select" id="reportEvents" multiple></select>
            </div>
            <button type="submit" class="btn btn-primary">Сформировать</button>
        </form>
        <div id="reportStatus" class="mt-3"></div>
        <div id="reportHistory" class="mt-4">
            <h5>История отчётов</h5>
            <table class="table table-sm" id="history-table">
                <thead><tr><th>Дата</th><th>Тип</th><th>Статус</th><th>Скачать</th></tr></thead>
                <tbody id="history-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin-reports.js"></script>
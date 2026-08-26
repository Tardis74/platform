<?php
// views/admin/dashboard.php
?>
<h1 class="mb-4">Дашборд администратора</h1>
<div id="dashboard-content">
    <div class="row g-4" id="stats-cards">
        <!-- Карточки заполняются через JS -->
    </div>
    <div class="row mt-4">
        <div class="col-md-8">
            <h5>Последние события аудита</h5>
            <table class="table table-striped table-sm" id="audit-table">
                <thead><tr><th>Дата</th><th>Пользователь</th><th>Событие</th></tr></thead>
                <tbody id="audit-tbody"></tbody>
            </table>
        </div>
        <div class="col-md-4">
            <h5>Быстрые ссылки</h5>
            <ul class="list-group">
                <li class="list-group-item"><a href="/admin/users">Управление пользователями</a></li>
                <li class="list-group-item"><a href="/admin/classes">Классы и учебные годы</a></li>
                <li class="list-group-item"><a href="/admin/reports">Отчёты</a></li>
                <li class="list-group-item"><a href="/admin/audit">Журнал аудита</a></li>
            </ul>
        </div>
    </div>
</div>
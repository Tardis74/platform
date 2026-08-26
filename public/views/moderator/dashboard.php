<h1>Дашборд модератора</h1>
<p>Добро пожаловать, <span id="moderator-name">...</span>!</p>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Документы на проверке</h5>
                <p class="card-text display-4" id="documents-pending-count">0</p>
                <a href="/moderator/documents" class="btn btn-light">Перейти</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Достижения на проверке</h5>
                <p class="card-text display-4" id="achievements-pending-count">0</p>
                <a href="/moderator/achievements" class="btn btn-light">Перейти</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Заявки на мероприятия</h5>
                <p class="card-text display-4" id="registrations-pending-count">0</p>
                <a href="/moderator/registrations" class="btn btn-light">Перейти</a>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/moderator-dashboard.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initPage === 'function') initPage();
    });
</script>
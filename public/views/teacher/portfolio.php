<?php
// Определяем необходимое разрешение для данной страницы
$requiredPermission = 'achievements.view'; // заменить на конкретное разрешение

// Проверяем, если роль custom и нет нужного разрешения – запрещаем доступ
if ($_SESSION['role'] === 'custom' && !in_array($requiredPermission, $_SESSION['permissions'] ?? [])) {
    echo '<div class="alert alert-danger">Доступ запрещён. <a href="/custom/dashboard">На главную</a></div>';
    return; // прекращаем выполнение шаблона
}
?>
<?php
$pageTitle = 'Портфолио класса';
$pageScript = '/assets/js/teacher-portfolio.js';
?>
<div class="container mt-4">
    <h1 class="mb-4">Портфолио класса</h1>
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="student-select" class="form-label">Ученик</label>
                    <select class="form-select" id="student-select">
                        <option value="">Выберите ученика</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-category" class="form-label">Категория</label>
                    <select class="form-select" id="filter-category">
                        <option value="">Все</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-year" class="form-label">Год</label>
                    <select class="form-select" id="filter-year">
                        <option value="">Все</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="apply-portfolio-filters">Применить</button>
                </div>
            </div>
            <div id="portfolio-summary" class="mb-3">
                <span>Сумма баллов: <strong id="total-points">0</strong></span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="portfolio-table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Статус</th>
                            <th>Баллы</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="portfolio-body">
                        <tr><td colspan="6" class="text-muted">Выберите ученика</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для комментария при отклонении достижения -->
<div class="modal fade" id="reject-achievement-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Причина отклонения</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="reject-achievement-reason" class="form-label">Комментарий</label>
                    <textarea class="form-control" id="reject-achievement-reason" rows="3"></textarea>
                </div>
                <input type="hidden" id="reject-achievement-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirm-reject-achievement">Отклонить</button>
            </div>
        </div>
    </div>
</div>
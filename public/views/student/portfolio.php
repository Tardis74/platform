<?php
/**
 * Портфолио ученика.
 */
?>
<div id="student-portfolio">
    <h2>Портфолио</h2>

    <!-- Сводка баллов и рейтинга -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Общая сумма баллов</h5>
                    <p class="display-6" id="portfolio-total-points">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Место в рейтинге</h5>
                    <p class="display-6" id="portfolio-rating-place">—</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры достижений -->
    <div class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="filterCategoryPortfolio" class="form-label">Категория</label>
            <select id="filterCategoryPortfolio" class="form-select">
                <option value="">Все</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filterYear" class="form-label">Год</label>
            <select id="filterYear" class="form-select">
                <option value="">Все</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filterStatusPortfolio" class="form-label">Статус</label>
            <select id="filterStatusPortfolio" class="form-select">
                <option value="">Все</option>
                <option value="pending">На проверке</option>
                <option value="approved">Подтверждено</option>
                <option value="rejected">Отклонено</option>
            </select>
        </div>
        <div class="col-md-3">
            <button id="applyPortfolioFilters" class="btn btn-primary w-100">Применить</button>
        </div>
    </div>

    <!-- Список достижений -->
    <table class="table table-striped" id="achievements-table">
        <thead>
            <tr>
                <th>Название</th>
                <th>Категория</th>
                <th>Статус</th>
                <th>Баллы</th>
                <th>Дата загрузки</th>
            </tr>
        </thead>
        <tbody id="achievements-body">
            <tr><td colspan="5" class="text-muted">Загрузка...</td></tr>
        </tbody>
    </table>

    <!-- Кнопка добавления -->
    <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
        + Добавить достижение
    </button>
</div>

<!-- Модальное окно добавления достижения -->
<div class="modal fade" id="addAchievementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addAchievementForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить достижение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="achievementCategory" class="form-label">Категория *</label>
                        <select id="achievementCategory" class="form-select" required>
                            <option value="">Выберите...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="achievementTitle" class="form-label">Название *</label>
                        <input type="text" id="achievementTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="achievementDescription" class="form-label">Описание</label>
                        <textarea id="achievementDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="achievementFile" class="form-label">Файл (изображение или PDF) *</label>
                        <input type="file" id="achievementFile" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/student-portfolio.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initPage();
    });
</script>
<?php
/**
 * Страница документов – список, фильтры, форма загрузки.
 */
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>📄 Документы</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
            <i class="bi bi-upload"></i> Загрузить
        </button>
    </div>

    <!-- Фильтры -->
    <div class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label">Ученик</label>
            <select class="form-select" id="filterStudent">
                <option value="">Все</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Статус</label>
            <select class="form-select" id="filterStatus">
                <option value="">Все</option>
                <option value="pending">На проверке</option>
                <option value="approved">Действителен</option>
                <option value="expired">Истёк</option>
                <option value="rejected">Отклонён</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Шаблон</label>
            <select class="form-select" id="filterTemplate">
                <option value="">Все</option>
            </select>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-primary" id="applyDocFilters">Применить</button>
        </div>
    </div>

    <!-- Таблица документов -->
    <div class="table-responsive">
        <table class="table table-hover" id="documentsTable">
            <thead>
                <tr>
                    <th>Ученик</th>
                    <th>Шаблон</th>
                    <th>Статус</th>
                    <th>Срок действия</th>
                    <th>Дата загрузки</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="documentsTableBody">
                <!-- Загружается через JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Модальное окно загрузки документа -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузить документ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadDocumentForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="docStudent" class="form-label">Ученик</label>
                        <select class="form-select" id="docStudent" required>
                            <option value="">Выберите...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="docTemplate" class="form-label">Шаблон</label>
                        <select class="form-select" id="docTemplate" required>
                            <option value="">Выберите...</option>
                        </select>
                    </div>
                    <div class="mb-3" id="docFileContainer">
                        <label for="docFile" class="form-label">Файл</label>
                        <input type="file" class="form-control" id="docFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.odt">
                    </div>
                    <div class="mb-3 d-none" id="docSignatureContainer">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="docSignature">
                            <label class="form-check-label" for="docSignature">Подтверждаю</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="docExpiry" class="form-label">Срок действия (опционально)</label>
                        <input type="date" class="form-control" id="docExpiry">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Загрузить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/parent-documents.js"></script>
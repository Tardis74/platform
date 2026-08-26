<?php
$pageTitle = 'Подтверждение учеников';
$pageScript = '/assets/js/teacher-confirm.js';
?>
<div class="container mt-4">
    <h1 class="mb-4">Подтверждение учеников</h1>
    <div class="card">
        <div class="card-body">
            <div id="pending-list">
                <p class="text-muted">Загрузка...</p>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для комментария при отклонении -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Причина отклонения</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="reject-reason" class="form-label">Комментарий</label>
                    <textarea class="form-control" id="reject-reason" rows="3"></textarea>
                </div>
                <input type="hidden" id="reject-student-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirm-reject">Отклонить</button>
            </div>
        </div>
    </div>
</div>
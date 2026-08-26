<?php
$pageTitle = 'Заявления на выход';
$pageScript = '/assets/js/teacher-leave.js';
?>
<div class="container mt-4">
    <h1 class="mb-4">Заявления на выход учеников класса</h1>
    <div class="card">
        <div class="card-body">
            <div id="leave-list">
                <p class="text-muted">Загрузка...</p>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения с корректировкой времени -->
<div class="modal fade" id="approve-leave-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение заявления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="leave-end-time" class="form-label">Время возврата (можно скорректировать)</label>
                    <input type="datetime-local" class="form-control" id="leave-end-time">
                </div>
                <input type="hidden" id="leave-request-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" id="confirm-approve-leave">Подтвердить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для комментария при отклонении -->
<div class="modal fade" id="reject-leave-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Причина отклонения</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="leave-reject-reason" class="form-label">Комментарий</label>
                    <textarea class="form-control" id="leave-reject-reason" rows="3"></textarea>
                </div>
                <input type="hidden" id="leave-reject-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirm-reject-leave">Отклонить</button>
            </div>
        </div>
    </div>
</div>
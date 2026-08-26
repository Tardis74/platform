<?php
/**
 * Заявления на выход.
 * Доступно только ученикам, проживающим в общежитии.
 * Если is_dormitory = false, показываем сообщение.
 */
?>
<div id="student-leave">
    <h2>Заявления на выход</h2>

    <div id="leave-module">
        <!-- Таблица заявлений -->
        <div class="table-responsive">
            <table class="table table-striped" id="leave-requests-table">
                <thead>
                    <tr>
                        <th>Дата подачи</th>
                        <th>Время выхода</th>
                        <th>Время возврата</th>
                        <th>Статус</th>
                        <th>QR-код</th>
                    </tr>
                </thead>
                <tbody id="leave-requests-body">
                    <tr><td colspan="5" class="text-muted">Загрузка...</td></tr>
                </tbody>
            </table>
        </div>

        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addLeaveRequestModal" id="addLeaveButton">
            + Подать заявление
        </button>
    </div>

    <div id="leave-not-dormitory" class="alert alert-warning" style="display:none;">
        Модуль заявлений на выход недоступен, так как вы не проживаете в общежитии.
    </div>
</div>

<!-- Модальное окно подачи заявления -->
<div class="modal fade" id="addLeaveRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addLeaveRequestForm">
                <div class="modal-header">
                    <h5 class="modal-title">Подать заявление на выход</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="leaveStartTime" class="form-label">Время выхода *</label>
                        <input type="datetime-local" id="leaveStartTime" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="leaveEndTime" class="form-label">Время возврата *</label>
                        <input type="datetime-local" id="leaveEndTime" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="leaveComment" class="form-label">Комментарий</label>
                        <textarea id="leaveComment" class="form-control" rows="3"></textarea>
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

<script src="/assets/js/student-leave.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initPage();
    });
</script>
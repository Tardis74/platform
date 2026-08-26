/**
 * Заявления на выход учеников класса.
 */
let leaveRequests = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadLeaveRequests();
    setupEventListeners();
}

async function loadLeaveRequests() {
    showLoading(true);
    try {
        leaveRequests = await apiCall('teacher.getLeaveRequests');
        renderLeaveRequests();
    } catch (e) {
        showToast('Ошибка загрузки заявлений: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderLeaveRequests() {
    const container = document.getElementById('leave-list');
    if (!leaveRequests || leaveRequests.length === 0) {
        container.innerHTML = '<p class="text-muted">Нет заявлений.</p>';
        return;
    }

    let html = `<div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>Ученик</th><th>Время выхода</th><th>Время возврата</th><th>Статус</th><th>Действия</th></tr></thead><tbody>`;
    leaveRequests.forEach(r => {
        const statusMap = { pending: 'Ожидает', approved: 'Подтверждено', rejected: 'Отклонено', exited: 'Вышел', returned: 'Вернулся' };
        const statusClass = { pending: 'warning', approved: 'success', rejected: 'danger', exited: 'primary', returned: 'info' }[r.status] || 'secondary';
        let actions = '';
        if (r.status === 'pending') {
            actions = `
                <button class="btn btn-sm btn-success approve-leave" data-id="${r.id}">Подтвердить</button>
                <button class="btn btn-sm btn-danger reject-leave" data-id="${r.id}">Отклонить</button>
            `;
        } else {
            actions = '—';
        }
        html += `
            <tr>
                <td>${r.student_name}</td>
                <td>${new Date(r.start_time).toLocaleString()}</td>
                <td>${new Date(r.end_time).toLocaleString()}</td>
                <td><span class="badge bg-${statusClass}">${statusMap[r.status] || r.status}</span></td>
                <td>${actions}</td>
            </tr>
        `;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function setupEventListeners() {
    // Подтверждение – открываем модалку с корректировкой времени
    document.getElementById('leave-list').addEventListener('click', function(e) {
        const target = e.target.closest('.approve-leave');
        if (target) {
            const requestId = target.dataset.id;
            const request = leaveRequests.find(r => r.id == requestId);
            if (request) {
                document.getElementById('leave-request-id').value = requestId;
                document.getElementById('leave-end-time').value = request.end_time.slice(0, 16);
                const modal = new bootstrap.Modal(document.getElementById('approve-leave-modal'));
                modal.show();
            }
        }
    });

    // Подтверждение в модалке
    document.getElementById('confirm-approve-leave').addEventListener('click', async function() {
        const requestId = document.getElementById('leave-request-id').value;
        const endTime = document.getElementById('leave-end-time').value;
        if (!endTime) {
            showToast('Укажите время возврата', 'warning');
            return;
        }
        showLoading(true);
        try {
            await apiCall('educator.approveLeave', {
                request_id: requestId,
                new_end_time: endTime
            });
            showToast('Заявление подтверждено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('approve-leave-modal')).hide();
            await loadLeaveRequests();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Отклонение – открываем модалку
    document.getElementById('leave-list').addEventListener('click', function(e) {
        const target = e.target.closest('.reject-leave');
        if (target) {
            const requestId = target.dataset.id;
            document.getElementById('leave-reject-id').value = requestId;
            document.getElementById('leave-reject-reason').value = '';
            const modal = new bootstrap.Modal(document.getElementById('reject-leave-modal'));
            modal.show();
        }
    });

    // Подтверждение отклонения
    document.getElementById('confirm-reject-leave').addEventListener('click', async function() {
        const requestId = document.getElementById('leave-reject-id').value;
        const reason = document.getElementById('leave-reject-reason').value.trim();
        if (!reason) {
            showToast('Укажите причину отклонения', 'warning');
            return;
        }
        showLoading(true);
        try {
            await apiCall('educator.rejectLeave', {
                request_id: requestId,
                reason: reason
            });
            showToast('Заявление отклонено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('reject-leave-modal')).hide();
            await loadLeaveRequests();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
}
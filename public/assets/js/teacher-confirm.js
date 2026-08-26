/**
 * Подтверждение учеников.
 */
let pendingStudents = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadPendingStudents();
    setupEventListeners();
}

async function loadPendingStudents() {
    showLoading(true);
    try {
        pendingStudents = await apiCall('teacher.getPendingStudents');
        renderPendingList();
    } catch (e) {
        showToast('Ошибка загрузки списка: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderPendingList() {
    const container = document.getElementById('pending-list');
    if (!pendingStudents || pendingStudents.length === 0) {
        container.innerHTML = '<p class="text-muted">Нет учеников, ожидающих подтверждения.</p>';
        return;
    }

    let html = `<div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>ФИО</th><th>Дата рождения</th><th>СНИЛС</th><th>Дата подачи</th><th>Действия</th></tr></thead><tbody>`;
    pendingStudents.forEach(s => {
        html += `
            <tr data-id="${s.id}">
                <td>${s.full_name}</td>
                <td>${s.birth_date || '—'}</td>
                <td>${s.snils_masked || '—'}</td>
                <td>${new Date(s.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-sm btn-success confirm-btn" data-id="${s.id}">Подтвердить</button>
                    <button class="btn btn-sm btn-danger reject-btn" data-id="${s.id}">Отклонить</button>
                </td>
            </tr>
        `;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function setupEventListeners() {
    // Подтверждение
    document.getElementById('pending-list').addEventListener('click', async function(e) {
        const target = e.target.closest('.confirm-btn');
        if (target) {
            const studentId = target.dataset.id;
            if (confirm('Подтвердить ученика?')) {
                showLoading(true);
                try {
                    await apiCall('teacher.confirmStudent', { student_id: studentId });
                    showToast('Ученик подтверждён', 'success');
                    await loadPendingStudents();
                } catch (e) {
                    showToast(e.message, 'danger');
                } finally {
                    showLoading(false);
                }
            }
        }
    });

    // Отклонение – открываем модалку
    document.getElementById('pending-list').addEventListener('click', function(e) {
        const target = e.target.closest('.reject-btn');
        if (target) {
            const studentId = target.dataset.id;
            document.getElementById('reject-student-id').value = studentId;
            document.getElementById('reject-reason').value = '';
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }
    });

    // Подтверждение отклонения
    document.getElementById('confirm-reject').addEventListener('click', async function() {
        const studentId = document.getElementById('reject-student-id').value;
        const reason = document.getElementById('reject-reason').value.trim();
        if (!reason) {
            showToast('Укажите причину отклонения', 'warning');
            return;
        }
        showLoading(true);
        try {
            await apiCall('teacher.rejectStudent', { student_id: studentId, reason });
            showToast('Ученик отклонён', 'success');
            bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
            await loadPendingStudents();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
}
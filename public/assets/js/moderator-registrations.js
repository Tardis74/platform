let allRegistrations = [];
let currentFilters = { student: '', event: '' };

async function loadRegistrations() {
    showLoading(true);
    try {
        allRegistrations = await apiCall('moderator.getPendingRegistrations');
        renderRegistrations();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderRegistrations() {
    const tbody = document.getElementById('registrations-body');
    const filtered = allRegistrations.filter(reg => {
        const studentMatch = reg.student_name.toLowerCase().includes(currentFilters.student.toLowerCase());
        const eventMatch = reg.event_title.toLowerCase().includes(currentFilters.event.toLowerCase());
        return studentMatch && eventMatch;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Нет заявок на проверке</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(reg => `
        <tr>
            <td>${reg.student_name}</td>
            <td>${reg.class_name || ''}</td>
            <td>${reg.event_title}</td>
            <td>${reg.event_start ? new Date(reg.event_start).toLocaleString() : ''}</td>
            <td>${new Date(reg.registered_at).toLocaleString()}</td>
            <td>
                <button class="btn btn-sm btn-success approve-reg" data-id="${reg.registration_id}">Подтвердить</button>
                <button class="btn btn-sm btn-danger reject-reg" data-id="${reg.registration_id}" data-bs-toggle="modal" data-bs-target="#rejectModal">Отклонить</button>
            </td>
        </tr>
    `).join('');

    document.querySelectorAll('.approve-reg').forEach(btn => {
        btn.addEventListener('click', function() {
            confirmRegistration(this.dataset.id);
        });
    });
    document.querySelectorAll('.reject-reg').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reject-registration-id').value = this.dataset.id;
            document.getElementById('reject-comment').value = '';
        });
    });
}

async function confirmRegistration(id) {
    showLoading(true);
    try {
        await apiCall('moderator.confirmRegistration', { registration_id: id });
        showToast('Заявка подтверждена', 'success');
        await loadRegistrations();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

async function rejectRegistration() {
    const id = document.getElementById('reject-registration-id').value;
    const comment = document.getElementById('reject-comment').value.trim();
    if (!comment) {
        showToast('Укажите причину отклонения', 'warning');
        return;
    }
    showLoading(true);
    try {
        await apiCall('moderator.rejectRegistration', { registration_id: id, reason: comment });
        showToast('Заявка отклонена', 'success');
        $('#rejectModal').modal('hide');
        await loadRegistrations();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function initPage() {
    document.getElementById('filter-student').addEventListener('input', function() {
        currentFilters.student = this.value;
        renderRegistrations();
    });
    document.getElementById('filter-event').addEventListener('input', function() {
        currentFilters.event = this.value;
        renderRegistrations();
    });
    document.getElementById('clear-filters').addEventListener('click', function() {
        document.getElementById('filter-student').value = '';
        document.getElementById('filter-event').value = '';
        currentFilters.student = '';
        currentFilters.event = '';
        renderRegistrations();
    });

    document.getElementById('confirm-reject').addEventListener('click', rejectRegistration);

    loadRegistrations();
}
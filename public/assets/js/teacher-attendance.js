/**
 * Ежедневные отметки о питании.
 */
let currentDate = new Date().toISOString().split('T')[0];
let attendanceData = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    document.getElementById('attendance-date').value = currentDate;
    await loadAttendance();
    setupEventListeners();
}

async function loadAttendance() {
    const date = document.getElementById('attendance-date').value;
    if (!date) return;

    showLoading(true);
    try {
        const data = await apiCall('teacher.attendance.get', { date });
        attendanceData = data || [];
        renderAttendance();
    } catch (e) {
        showToast('Ошибка загрузки отметок: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderAttendance() {
    const tbody = document.getElementById('attendance-body');
    if (!attendanceData || attendanceData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-muted">Нет учеников.</td></tr>';
        return;
    }

    let html = '';
    attendanceData.forEach(row => {
        const checked = row.is_present ? 'checked' : '';
        html += `
            <tr>
                <td>${row.full_name}</td>
                <td>
                    <input type="checkbox" class="form-check-input attendance-check" data-student-id="${row.student_id}" ${checked}>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function setupEventListeners() {
    // Изменение даты
    document.getElementById('attendance-date').addEventListener('change', loadAttendance);

    // Отметить всех
    document.getElementById('mark-all').addEventListener('click', function() {
        document.querySelectorAll('.attendance-check').forEach(cb => cb.checked = true);
    });

    // Снять все
    document.getElementById('unmark-all').addEventListener('click', function() {
        document.querySelectorAll('.attendance-check').forEach(cb => cb.checked = false);
    });

    // Сохранить
    document.getElementById('save-attendance').addEventListener('click', async function() {
        const studentIds = [];
        document.querySelectorAll('.attendance-check:checked').forEach(cb => {
            studentIds.push(parseInt(cb.dataset.studentId));
        });

        const date = document.getElementById('attendance-date').value;
        if (!date) {
            showToast('Выберите дату', 'warning');
            return;
        }

        showLoading(true);
        try {
            await apiCall('teacher.attendance.mark', { date, student_ids: studentIds });
            showToast('Отметки сохранены', 'success');
            await loadAttendance(); // обновить
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
}
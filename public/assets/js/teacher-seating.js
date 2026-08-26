/**
 * Управление рассадкой столовой.
 */
let seatingData = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadSeating();
    setupEventListeners();
}

async function loadSeating() {
    showLoading(true);
    try {
        seatingData = await apiCall('teacher.seating.get');
        renderSeating();
    } catch (e) {
        showToast('Ошибка загрузки рассадки: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderSeating() {
    const tbody = document.getElementById('seating-body');
    if (!seatingData || seatingData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-muted">Нет данных о рассадке.</td></tr>';
        return;
    }

    let html = '';
    seatingData.forEach(row => {
        html += `
            <tr data-student-id="${row.student_id}">
                <td>${row.full_name}</td>
                <td><input type="number" class="form-control form-control-sm table-input" data-field="table_number" value="${row.table_number || ''}" min="1"></td>
                <td><input type="number" class="form-control form-control-sm table-input" data-field="seat_number" value="${row.seat_number || ''}" min="1"></td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function setupEventListeners() {
    // Сохранить
    document.getElementById('save-seating').addEventListener('click', async function() {
        const seats = [];
        document.querySelectorAll('#seating-body tr').forEach(tr => {
            const studentId = tr.dataset.studentId;
            const table = tr.querySelector('[data-field="table_number"]').value;
            const seat = tr.querySelector('[data-field="seat_number"]').value;
            seats.push({
                student_id: studentId,
                table_number: table ? parseInt(table) : null,
                seat_number: seat ? parseInt(seat) : null
            });
        });

        showLoading(true);
        try {
            await apiCall('teacher.seating.set', { seats });
            showToast('Рассадка сохранена', 'success');
            await loadSeating(); // обновить
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Очистить
    document.getElementById('clear-seating').addEventListener('click', async function() {
        if (!confirm('Очистить всю рассадку для класса?')) return;
        showLoading(true);
        try {
            await apiCall('teacher.seating.clear');
            showToast('Рассадка очищена', 'success');
            await loadSeating();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
}
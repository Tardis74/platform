// assets/js/admin-reports.js
async function loadReportClasses() {
    const classes = await apiCall('admin.class.list');
    const sel = document.getElementById('reportClasses');
    sel.innerHTML = classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
}

async function loadReportStudents(classIds) {
    // загружаем учеников по классам
    if (!classIds || classIds.length === 0) return;
    const students = await apiCall('admin.student.list', { class_ids: classIds });
    const sel = document.getElementById('reportStudents');
    sel.innerHTML = students.map(s => `<option value="${s.id}">${s.full_name}</option>`).join('');
}

function initPage() {
    loadReportClasses();
    document.getElementById('reportClasses').addEventListener('change', function() {
        const selected = Array.from(this.selectedOptions).map(o => o.value);
        loadReportStudents(selected);
    });
    // При смене типа отчёта показываем/скрываем выбор мероприятий
    document.getElementById('reportType').addEventListener('change', function() {
        document.getElementById('reportEventContainer').style.display = this.value === 'events' ? 'block' : 'none';
    });
    // Генерация
    document.getElementById('reportForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const type = document.getElementById('reportType').value;
        const params = {
            type,
            date_from: document.getElementById('dateFrom').value,
            date_to: document.getElementById('dateTo').value,
            class_ids: Array.from(document.getElementById('reportClasses').selectedOptions).map(o => o.value),
            student_ids: Array.from(document.getElementById('reportStudents').selectedOptions).map(o => o.value),
            event_ids: Array.from(document.getElementById('reportEvents').selectedOptions).map(o => o.value)
        };
        try {
            const result = await apiCall('admin.report.generate', params);
            showToast('Отчёт поставлен в очередь', 'success');
            checkStatus(result.job_id);
        } catch (err) { showToast(err.message, 'danger'); }
    });
    // История
    loadHistory();
}

async function checkStatus(jobId) {
    const interval = setInterval(async () => {
        try {
            const status = await apiCall('admin.report.status', { job_id: jobId });
            document.getElementById('reportStatus').innerHTML = `Статус: ${status.status}`;
            if (status.status === 'ready') {
                clearInterval(interval);
                document.getElementById('reportStatus').innerHTML += ` <a href="${status.download_url}">Скачать</a>`;
            } else if (status.status === 'error') {
                clearInterval(interval);
                showToast('Ошибка генерации отчёта', 'danger');
            }
        } catch (e) { clearInterval(interval); showToast(e.message, 'danger'); }
    }, 3000);
}

async function loadHistory() {
    try {
        const history = await apiCall('admin.report.history');
        const tbody = document.getElementById('history-tbody');
        tbody.innerHTML = history.map(item => `
            <tr>
                <td>${item.created_at}</td>
                <td>${item.type}</td>
                <td>${item.status}</td>
                <td>${item.download_url ? `<a href="${item.download_url}">Скачать</a>` : '—'}</td>
            </tr>
        `).join('');
    } catch (e) { /* игнорируем */ }
}
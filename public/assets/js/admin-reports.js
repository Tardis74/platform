// assets/js/admin-reports.js
console.log('admin-reports.js loaded');

async function loadReportClasses() {
    try {
        const classes = await apiCall('admin.classList');
        const sel = document.getElementById('reportClasses');
        if (!sel) return;
        sel.innerHTML = classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

async function loadReportStudents(classIds) {
    if (!classIds || classIds.length === 0) return;
    try {
        const students = await apiCall('admin.studentList', { class_ids: classIds });
        const sel = document.getElementById('reportStudents');
        if (!sel) return;
        sel.innerHTML = students.map(s => `<option value="${s.id}">${s.full_name}</option>`).join('');
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

async function loadHistory() {
    try {
        const history = await apiCall('admin.reportHistory');
        const tbody = document.getElementById('history-tbody');
        if (!tbody) return;
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

function checkStatus(jobId) {
    const interval = setInterval(async () => {
        try {
            const status = await apiCall('admin.reportStatus', { job_id: jobId });
            const el = document.getElementById('reportStatus');
            if (!el) return;
            el.innerHTML = `Статус: ${status.status}`;
            if (status.status === 'ready') {
                clearInterval(interval);
                el.innerHTML += ` <a href="${status.download_url}">Скачать</a>`;
            } else if (status.status === 'error') {
                clearInterval(interval);
                showToast('Ошибка генерации отчёта', 'danger');
            }
        } catch (e) {
            clearInterval(interval);
            showToast(e.message, 'danger');
        }
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    loadReportClasses();
    loadHistory();

    document.getElementById('reportClasses')?.addEventListener('change', function() {
        const selected = Array.from(this.selectedOptions).map(o => o.value);
        loadReportStudents(selected);
    });

    document.getElementById('reportType')?.addEventListener('change', function() {
        document.getElementById('reportEventContainer').style.display = this.value === 'events' ? 'block' : 'none';
    });

    document.getElementById('reportForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const type = document.getElementById('reportType')?.value;
        const params = {
            type,
            date_from: document.getElementById('dateFrom')?.value || '',
            date_to: document.getElementById('dateTo')?.value || '',
            class_ids: Array.from(document.getElementById('reportClasses')?.selectedOptions || []).map(o => o.value),
            student_ids: Array.from(document.getElementById('reportStudents')?.selectedOptions || []).map(o => o.value),
            event_ids: Array.from(document.getElementById('reportEvents')?.selectedOptions || []).map(o => o.value)
        };
        showLoading(true);
        try {
            const result = await apiCall('admin.reportGenerate', params);
            showToast('Отчёт поставлен в очередь', 'success');
            checkStatus(result.job_id);
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            showLoading(false);
        }
    });
});
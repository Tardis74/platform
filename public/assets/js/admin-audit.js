// assets/js/admin-audit.js
let auditPage = 1;

function initPage() {
    loadAudit();
    // Фильтры – при изменении перезагружаем
    document.querySelectorAll('#auditDateFrom, #auditDateTo, #auditUser, #auditEventType').forEach(el => {
        el.addEventListener('change', () => { auditPage = 1; loadAudit(); });
    });
    // Экспорт
    document.getElementById('auditExport').addEventListener('click', function() {
        const params = buildFilters();
        window.location.href = `/api.php?method=admin.audit.export&${new URLSearchParams(params)}`;
    });
}

async function loadAudit(page = 1) {
    try {
        const params = { page, limit: 20, ...buildFilters() };
        const data = await apiCall('admin.audit.list', params);
        const tbody = document.getElementById('audit-tbody');
        tbody.innerHTML = data.items.map(item => `
            <tr>
                <td>${item.created_at}</td>
                <td>${item.user_name}</td>
                <td>${item.ip_address}</td>
                <td>${item.event_type}</td>
                <td>${item.object_change}</td>
            </tr>
        `).join('');
        renderPagination(data.total, data.page, data.limit, 'audit-pagination', loadAudit);
    } catch (e) { showToast(e.message, 'danger'); }
}

function buildFilters() {
    return {
        date_from: document.getElementById('auditDateFrom').value,
        date_to: document.getElementById('auditDateTo').value,
        user: document.getElementById('auditUser').value.trim(),
        event_type: document.getElementById('auditEventType').value
    };
}
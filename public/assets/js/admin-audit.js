// assets/js/admin-audit.js
console.log('admin-audit.js loaded');

let auditPage = 1;

// --- Пагинация (дублируем, чтобы не зависеть от других файлов) ---
function renderPagination(total, page, limit, containerId, callback) {
    const pages = Math.ceil(total / limit);
    let html = '';
    for (let i = 1; i <= pages; i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = html;
    container.querySelectorAll('.page-link').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            callback(parseInt(el.dataset.page));
        });
    });
}

// --- Загрузка аудита ---
async function loadAudit(page = 1) {
    showLoading(true);
    try {
        const params = { page, limit: 20, ...buildFilters() };
        const data = await apiCall('admin.auditList', params);
        const tbody = document.getElementById('audit-tbody');
        if (!tbody) return;
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
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function buildFilters() {
    return {
        date_from: document.getElementById('auditDateFrom')?.value || '',
        date_to: document.getElementById('auditDateTo')?.value || '',
        user: document.getElementById('auditUser')?.value.trim() || '',
        event_type: document.getElementById('auditEventType')?.value || ''
    };
}

document.addEventListener('DOMContentLoaded', function() {
    loadAudit();

    // Фильтры
    document.querySelectorAll('#auditDateFrom, #auditDateTo, #auditUser, #auditEventType').forEach(el => {
        el.addEventListener('change', () => { auditPage = 1; loadAudit(); });
    });

    // Экспорт
    document.getElementById('auditExport')?.addEventListener('click', function() {
        const params = buildFilters();
        window.location.href = `/api.php?method=admin.auditExport&${new URLSearchParams(params)}`;
    });
});
// assets/js/admin-dashboard.js
console.log('admin-dashboard.js loaded');

document.addEventListener('DOMContentLoaded', async function() {
    showLoading(true);
    try {
        const stats = await apiCall('admin.getDashboardStats');
        const container = document.getElementById('stats-cards');
        if (!container) return;
        container.innerHTML = `
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>Пользователи</h5><h2>${stats.total_users}</h2><small>Админов: ${stats.admins}, Учителей: ${stats.teachers}, Родителей: ${stats.parents}, Учеников: ${stats.students}, Модераторов: ${stats.moderators}</small></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>Ученики</h5><h2>${stats.students}</h2><small>Активных: ${stats.active_students}</small></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>Классы</h5><h2>${stats.classes}</h2></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>Мероприятия</h5><h2>${stats.events}</h2></div></div></div>
        `;
        const audit = await apiCall('admin.auditList', { limit: 5 });
        const tbody = document.getElementById('audit-tbody');
        if (tbody) {
            tbody.innerHTML = audit.items.map(item => `
                <tr><td>${item.created_at}</td><td>${item.user_name}</td><td>${item.action}</td></tr>
            `).join('');
        }
    } catch (e) {
        showToast('Ошибка загрузки дашборда: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
});
/**
 * Дашборд классного руководителя.
 */
document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadDashboard();
}

async function loadDashboard() {
    showLoading(true);
    try {
        // Получаем данные пользователя
        const user = await getUser();
        if (!user) {
            showToast('Не удалось загрузить данные пользователя', 'danger');
            return;
        }

        // Загружаем статистику
        const stats = await apiCall('teacher.getDashboardStats');

        // Приветствие
        document.getElementById('greeting').textContent = `Здравствуйте, ${user.full_name || 'Учитель'}!`;
        document.getElementById('class-name').textContent = stats.class_name || '—';

        // Статистика
        document.getElementById('stat-students').textContent = stats.students_count || 0;
        document.getElementById('stat-pending').textContent = stats.pending_count || 0;
        document.getElementById('stat-leave').textContent = stats.leave_today || 0;
        document.getElementById('stat-events').textContent = stats.events_this_week || 0;
    } catch (e) {
        showToast('Ошибка загрузки дашборда: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}
/**
 * Инициализация дашборда модератора.
 */
async function initPage() {
    showLoading(true);
    try {
        // Загружаем статистику тремя параллельными запросами
        const [documents, achievements, registrations] = await Promise.all([
            apiCall('moderator.getPendingDocuments'),
            apiCall('moderator.getPendingAchievements'),
            apiCall('moderator.getPendingRegistrations')
        ]);

        document.getElementById('documents-pending-count').textContent = documents.length;
        document.getElementById('achievements-pending-count').textContent = achievements.length;
        document.getElementById('registrations-pending-count').textContent = registrations.length;

        // Имя пользователя
        const user = await getUser();
        if (user) {
            document.getElementById('moderator-name').textContent = user.full_name || user.email;
        }
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}
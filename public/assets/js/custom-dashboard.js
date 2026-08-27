function initPage() {
    const user = window.user;
    const permissions = window.userPermissions || [];

    document.getElementById('userFullName').textContent = user?.full_name || 'Пользователь';

    const permList = document.getElementById('permissionsList');
    if (permissions.length === 0) {
        permList.innerHTML = '<span class="text-muted">У вас нет персональных разрешений.</span>';
    } else {
        permList.innerHTML = permissions.join(', ');
    }

    const menuContainer = document.getElementById('customMenu');
    const menuMap = [
        { permission: 'users.view', url: '/admin/users', label: 'Управление пользователями' },
        { permission: 'classes.view', url: '/admin/classes', label: 'Классы' },
        { permission: 'tags.view', url: '/admin/tags', label: 'Теги' },
        { permission: 'templates.view', url: '/admin/templates', label: 'Шаблоны' },
        { permission: 'categories.view', url: '/admin/categories', label: 'Категории' },
        { permission: 'reports.view', url: '/admin/reports', label: 'Отчёты' },
        { permission: 'rating.view', url: '/admin/rating', label: 'Рейтинг' },
        { permission: 'permissions.view', url: '/admin/permissions', label: 'Права доступа' },
        { permission: 'audit.view', url: '/admin/audit', label: 'Аудит' },
        { permission: 'events.view', url: '/admin/events', label: 'Мероприятия' },
        { permission: 'documents.view', url: '/admin/documents', label: 'Документы' },
        { permission: 'achievements.view', url: '/admin/achievements', label: 'Достижения' },
        { permission: 'leave.view', url: '/admin/leave', label: 'Заявления' },
        { permission: 'canteen.view', url: '/admin/canteen', label: 'Питание' },
        { permission: 'kpp.view', url: '/admin/kpp', label: 'КПП' },
    ];

    let items = menuMap.filter(item => permissions.includes(item.permission) || permissions.includes('*'));
    if (items.length === 0) {
        menuContainer.innerHTML = '<div class="alert alert-warning">У вас нет доступа ни к одному разделу. Обратитесь к администратору.</div>';
    } else {
        menuContainer.innerHTML = items.map(item =>
            `<a href="${item.url}" class="list-group-item list-group-item-action">${item.label}</a>`
        ).join('');
    }
}
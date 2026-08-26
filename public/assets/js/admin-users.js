// assets/js/admin-users.js
let currentPage = 1;
let filters = { role: '', status: '', search: '' };

async function loadUsers(page = 1) {
    showLoading(true);
    try {
        const data = await apiCall('admin.user.list', {
            page: page,
            limit: 20,
            role: filters.role,
            status: filters.status,
            search: filters.search
        });
        const tbody = document.getElementById('users-tbody');
        tbody.innerHTML = data.items.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.full_name}</td>
                <td>${user.email}</td>
                <td>${user.role}</td>
                <td>${user.created_at}</td>
                <td>${user.status === 'active' ? 'Активен' : 'Заблокирован'}</td>
                <td>
                    <button class="btn btn-sm btn-warning edit-user" data-id="${user.id}">Редактировать</button>
                    <button class="btn btn-sm ${user.status === 'active' ? 'btn-danger' : 'btn-success'} toggle-status" data-id="${user.id}">
                        ${user.status === 'active' ? 'Блокировать' : 'Разблокировать'}
                    </button>
                    <button class="btn btn-sm btn-secondary delete-user" data-id="${user.id}">Удалить</button>
                </td>
            </tr>
        `).join('');
        renderPagination(data.total, data.page, data.limit, 'users-pagination', loadUsers);
    } catch (e) {
        showToast('Ошибка загрузки пользователей: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderPagination(total, page, limit, containerId, callback) {
    const pages = Math.ceil(total / limit);
    let html = '';
    for (let i = 1; i <= pages; i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    document.getElementById(containerId).innerHTML = html;
    document.querySelectorAll(`#${containerId} .page-link`).forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            callback(parseInt(el.dataset.page));
        });
    });
}

function initPage() {
    loadUsers();
    // Фильтры
    document.getElementById('filterRole').addEventListener('change', function() {
        filters.role = this.value;
        loadUsers(1);
    });
    document.getElementById('filterStatus').addEventListener('change', function() {
        filters.status = this.value;
        loadUsers(1);
    });
    document.getElementById('searchBtn').addEventListener('click', function() {
        filters.search = document.getElementById('userSearch').value.trim();
        loadUsers(1);
    });
    // Создание
    document.getElementById('createUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.user.create', data);
            showToast('Пользователь создан', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
            loadUsers(currentPage);
        } catch (err) { showToast(err.message, 'danger'); }
    });
    // Редактирование
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-user')) {
            const id = e.target.dataset.id;
            apiCall('admin.user.get', { id }).then(user => {
                const form = document.getElementById('editUserForm');
                form.elements['id'].value = user.id;
                form.elements['full_name'].value = user.full_name;
                form.elements['email'].value = user.email;
                form.elements['role'].value = user.role;
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });
    document.getElementById('editUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.user.update', data);
            showToast('Пользователь обновлён', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            loadUsers(currentPage);
        } catch (err) { showToast(err.message, 'danger'); }
    });
    // Блокировка/разблокировка
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('toggle-status')) {
            const id = e.target.dataset.id;
            const action = e.target.textContent.trim() === 'Блокировать' ? 'block' : 'unblock';
            if (confirm(`Вы уверены, что хотите ${action === 'block' ? 'заблокировать' : 'разблокировать'} пользователя?`)) {
                apiCall('admin.user.toggleStatus', { id, action }).then(() => {
                    showToast('Статус изменён', 'success');
                    loadUsers(currentPage);
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });
    // Удаление
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-user')) {
            const id = e.target.dataset.id;
            if (confirm('Удалить пользователя?')) {
                apiCall('admin.user.delete', { id }).then(() => {
                    showToast('Пользователь удалён', 'success');
                    loadUsers(currentPage);
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });
    // Импорт
    document.getElementById('importUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        try {
            const result = await apiCall('admin.user.importPreview', formData);
            document.getElementById('importPreview').style.display = 'block';
            document.getElementById('importTableContainer').innerHTML = result.html;
            window.importData = result.data;
            document.getElementById('confirmImport').style.display = 'inline-block';
        } catch (err) { showToast(err.message, 'danger'); }
    });
    document.getElementById('confirmImport').addEventListener('click', async function() {
        try {
            await apiCall('admin.user.import', { data: window.importData });
            showToast('Импорт выполнен', 'success');
            bootstrap.Modal.getInstance(document.getElementById('importUserModal')).hide();
            loadUsers(1);
        } catch (err) { showToast(err.message, 'danger'); }
    });
}
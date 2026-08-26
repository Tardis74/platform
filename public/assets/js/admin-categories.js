// assets/js/admin-categories.js
async function loadCategories() {
    try {
        const categories = await apiCall('admin.category.list');
        const tbody = document.getElementById('categories-tbody');
        tbody.innerHTML = categories.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>${c.weight}</td>
                <td>
                    <button class="btn btn-sm btn-warning edit-category" data-id="${c.id}">Редактировать</button>
                    <button class="btn btn-sm btn-danger delete-category" data-id="${c.id}">Удалить</button>
                </td>
            </tr>
        `).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

function initPage() {
    loadCategories();
    // Создание
    document.getElementById('createCategoryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.category.create', data);
            showToast('Категория создана', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createCategoryModal')).hide();
            loadCategories();
        } catch (err) { showToast(err.message, 'danger'); }
    });
    // Редактирование
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-category')) {
            const id = e.target.dataset.id;
            apiCall('admin.category.get', { id }).then(cat => {
                const form = document.getElementById('editCategoryForm');
                form.elements['id'].value = cat.id;
                form.elements['name'].value = cat.name;
                form.elements['weight'].value = cat.weight;
                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });
    document.getElementById('editCategoryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.category.update', data);
            showToast('Категория обновлена', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
            loadCategories();
        } catch (err) { showToast(err.message, 'danger'); }
    });
    // Удаление
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-category')) {
            const id = e.target.dataset.id;
            if (confirm('Удалить категорию?')) {
                apiCall('admin.category.delete', { id }).then(() => {
                    showToast('Категория удалена', 'success');
                    loadCategories();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });
}
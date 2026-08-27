// assets/js/admin-categories.js
console.log('admin-categories.js loaded');

async function loadCategories() {
    showLoading(true);
    try {
        const categories = await apiCall('admin.categoryList');
        const tbody = document.getElementById('categories-tbody');
        if (!tbody) return;
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
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();

    // Создание
    document.getElementById('createCategoryForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.categoryCreate', data);
            showToast('Категория создана', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createCategoryModal'))?.hide();
            loadCategories();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Редактирование - загрузка данных
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-category')) {
            const id = e.target.dataset.id;
            apiCall('admin.categoryGet', { id }).then(cat => {
                const form = document.getElementById('editCategoryForm');
                if (!form) return;
                form.elements['id'].value = cat.id;
                form.elements['name'].value = cat.name;
                form.elements['weight'].value = cat.weight;
                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });

    // Сохранение редактирования
    document.getElementById('editCategoryForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.categoryUpdate', data);
            showToast('Категория обновлена', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editCategoryModal'))?.hide();
            loadCategories();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Удаление
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-category')) {
            const id = e.target.dataset.id;
            if (confirm('Удалить категорию?')) {
                apiCall('admin.categoryDelete', { id }).then(() => {
                    showToast('Категория удалена', 'success');
                    loadCategories();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });
});
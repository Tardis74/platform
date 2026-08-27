// assets/js/admin-tags.js
console.log('admin-tags.js loaded');

async function loadTags() {
    showLoading(true);
    try {
        const tags = await apiCall('admin.tagList');
        const tbody = document.querySelector('#tags-table tbody');
        if (!tbody) return;
        tbody.innerHTML = tags.map(tag => `
            <tr>
                <td>${tag.id}</td>
                <td>${tag.name}</td>
                <td>
                    <button class="btn btn-sm btn-warning edit-tag" data-id="${tag.id}">Редактировать</button>
                    <button class="btn btn-sm btn-danger delete-tag" data-id="${tag.id}">Удалить</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

async function loadTagCheckboxes(containerId, selected = []) {
    try {
        const tags = await apiCall('admin.tagList');
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = tags.map(t => `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="${t.id}" id="tag_${t.id}" ${selected.includes(t.id) ? 'checked' : ''}>
                <label class="form-check-label" for="tag_${t.id}">${t.name}</label>
            </div>
        `).join('');
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadTags();

    // Создание
    document.getElementById('createTagForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.tagCreate', data);
            showToast('Тег создан', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createTagModal'))?.hide();
            loadTags();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Редактирование - загрузка данных
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-tag')) {
            const id = e.target.dataset.id;
            apiCall('admin.tagGet', { id }).then(tag => {
                const form = document.getElementById('editTagForm');
                if (!form) return;
                form.elements['id'].value = tag.id;
                form.elements['name'].value = tag.name;
                new bootstrap.Modal(document.getElementById('editTagModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });

    // Сохранение редактирования
    document.getElementById('editTagForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.tagUpdate', data);
            showToast('Тег обновлён', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editTagModal'))?.hide();
            loadTags();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Удаление
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-tag')) {
            const id = e.target.dataset.id;
            if (confirm('Удалить тег?')) {
                apiCall('admin.tagDelete', { id }).then(() => {
                    showToast('Тег удалён', 'success');
                    loadTags();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });

    // Назначение тегов ученику
    document.getElementById('assignStudentSearch')?.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) return;
        apiCall('admin.studentFind', { query }).then(students => {
            const sugg = document.getElementById('assignStudentSuggestions');
            if (!sugg) return;
            sugg.innerHTML = students.map(s => `
                <button class="list-group-item list-group-item-action" data-id="${s.id}">${s.full_name} (${s.class_name})</button>
            `).join('');
            sugg.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('assignStudentSearch').value = this.textContent;
                    document.getElementById('assignStudentSearch').dataset.id = this.dataset.id;
                    document.querySelector('input[name="student_id"]').value = this.dataset.id;
                    sugg.innerHTML = '';
                    loadTagCheckboxes('assignTagCheckboxes');
                });
            });
        }).catch(() => {});
    });

    document.getElementById('assignTagForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const studentId = document.querySelector('input[name="student_id"]')?.value;
        const tagIds = Array.from(document.querySelectorAll('#assignTagCheckboxes input:checked')).map(el => el.value);
        if (!studentId || tagIds.length === 0) { showToast('Выберите ученика и теги', 'warning'); return; }
        try {
            await apiCall('admin.tagAssign', { student_id: studentId, tag_ids: tagIds });
            showToast('Теги назначены', 'success');
            bootstrap.Modal.getInstance(document.getElementById('assignTagModal'))?.hide();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Массовое назначение
    document.getElementById('massAssignForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const classId = document.querySelector('select[name="class_id"]')?.value;
        const tagIds = Array.from(document.querySelectorAll('#massAssignTagCheckboxes input:checked')).map(el => el.value);
        if (!classId || tagIds.length === 0) { showToast('Выберите класс и теги', 'warning'); return; }
        try {
            await apiCall('admin.tagAssignMass', { class_id: classId, tag_ids: tagIds });
            showToast('Теги назначены всем ученикам класса', 'success');
            bootstrap.Modal.getInstance(document.getElementById('massAssignModal'))?.hide();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });
});
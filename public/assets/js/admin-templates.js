// assets/js/admin-templates.js
console.log('admin-templates.js loaded');

async function loadTemplates() {
    showLoading(true);
    try {
        const templates = await apiCall('admin.templateList');
        const tbody = document.getElementById('templates-tbody');
        if (!tbody) return;
        tbody.innerHTML = templates.map(t => `
            <tr>
                <td>${t.name}</td>
                <td>${t.description || '—'}</td>
                <td>${t.signature_level}</td>
                <td>${t.requires_file ? 'Да' : 'Нет'}</td>
                <td>
                    <button class="btn btn-sm btn-primary preview-template" data-id="${t.id}">Предпросмотр</button>
                    <button class="btn btn-sm btn-warning edit-template" data-id="${t.id}">Редактировать</button>
                    <button class="btn btn-sm btn-danger delete-template" data-id="${t.id}">Удалить</button>
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
    loadTemplates();

    // Создание/редактирование
    document.getElementById('templateForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        data.requires_file = data.requires_file ? 1 : 0;
        const method = data.id ? 'admin.templateUpdate' : 'admin.templateCreate';
        try {
            await apiCall(method, data);
            showToast('Шаблон сохранён', 'success');
            bootstrap.Modal.getInstance(document.getElementById('templateModal'))?.hide();
            loadTemplates();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Предпросмотр содержимого
    document.getElementById('previewTemplate')?.addEventListener('click', function() {
        const content = document.querySelector('textarea[name="content"]')?.value;
        if (!content) { showToast('Содержимое шаблона пусто', 'warning'); return; }
        const testData = {
            STUDENT_FIO: 'Иванов Иван Иванович',
            PARENT_FIO: 'Иванова Мария Петровна',
            CLASS: '10А',
            DATE: '2026-08-26',
            EVENT_NAME: 'День знаний',
            EVENT_DATE: '2026-09-01'
        };
        let html = content;
        for (let [key, val] of Object.entries(testData)) {
            html = html.replace(new RegExp(`\\{${key}\\}`, 'g'), val);
        }
        const preview = document.getElementById('templatePreview');
        if (preview) {
            preview.innerHTML = html;
            preview.style.display = 'block';
        }
    });

    // Редактирование - загрузка данных
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-template')) {
            const id = e.target.dataset.id;
            apiCall('admin.templateGet', { id }).then(t => {
                const form = document.getElementById('templateForm');
                if (!form) return;
                form.elements['id'].value = t.id;
                form.elements['name'].value = t.name;
                form.elements['description'].value = t.description || '';
                form.elements['content'].value = t.content;
                form.elements['signature_level'].value = t.signature_level;
                form.elements['requires_file'].checked = t.requires_file;
                document.getElementById('templateModalTitle').textContent = 'Редактировать шаблон';
                new bootstrap.Modal(document.getElementById('templateModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });

    // Удаление
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-template')) {
            const id = e.target.dataset.id;
            if (confirm('Удалить шаблон?')) {
                apiCall('admin.templateDelete', { id }).then(() => {
                    showToast('Шаблон удалён', 'success');
                    loadTemplates();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });

    // Предпросмотр из кнопки в таблице
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('preview-template')) {
            const id = e.target.dataset.id;
            apiCall('admin.templateGet', { id }).then(t => {
                const testData = {
                    STUDENT_FIO: 'Иванов Иван Иванович',
                    PARENT_FIO: 'Иванова Мария Петровна',
                    CLASS: '10А',
                    DATE: '2026-08-26',
                    EVENT_NAME: 'День знаний',
                    EVENT_DATE: '2026-09-01'
                };
                let html = t.content;
                for (let [key, val] of Object.entries(testData)) {
                    html = html.replace(new RegExp(`\\{${key}\\}`, 'g'), val);
                }
                alert(html); // или можно открыть в модалке
            }).catch(err => showToast(err.message, 'danger'));
        }
    });
});
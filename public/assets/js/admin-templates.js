// assets/js/admin-templates.js
async function loadTemplates() {
    try {
        const templates = await apiCall('admin.template.list');
        const tbody = document.getElementById('templates-tbody');
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
    } catch (e) { showToast(e.message, 'danger'); }
}

function initPage() {
    loadTemplates();
    // Создание/редактирование
    document.getElementById('templateForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        data.requires_file = data.requires_file ? 1 : 0;
        const method = data.id ? 'admin.template.update' : 'admin.template.create';
        try {
            await apiCall(method, data);
            showToast('Шаблон сохранён', 'success');
            bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
            loadTemplates();
        } catch (err) { showToast(err.message, 'danger'); }
    });
    document.getElementById('previewTemplate').addEventListener('click', function() {
        const content = document.querySelector('textarea[name="content"]').value;
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
        document.getElementById('templatePreview').innerHTML = html;
        document.getElementById('templatePreview').style.display = 'block';
    });
    // Редактирование
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-template')) {
            const id = e.target.dataset.id;
            apiCall('admin.template.get', { id }).then(t => {
                const form = document.getElementById('templateForm');
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
                apiCall('admin.template.delete', { id }).then(() => {
                    showToast('Шаблон удалён', 'success');
                    loadTemplates();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });
    // Предпросмотр
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('preview-template')) {
            const id = e.target.dataset.id;
            apiCall('admin.template.get', { id }).then(t => {
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
                alert(html); // упрощённо, можно в модалке
            }).catch(err => showToast(err.message, 'danger'));
        }
    });
}
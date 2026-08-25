/**
 * Страница документов – список, фильтры, загрузка.
 */
let allDocuments = [];
let children = [];
let templates = [];

document.addEventListener('DOMContentLoaded', function() {
    initPage();
});

async function initPage() {
    await loadChildrenAndTemplates();
    await loadDocuments();
    setupEventListeners();
}

async function loadChildrenAndTemplates() {
    try {
        [children, templates] = await Promise.all([
            apiCall('parent.getChildren'),
            apiCall('admin.template.list')
        ]);
        // Заполняем фильтры и форму
        const studentSelects = ['filterStudent', 'docStudent'];
        studentSelects.forEach(id => {
            const sel = document.getElementById(id);
            if (sel) {
                sel.innerHTML = '<option value="">Выберите...</option>' + 
                    children.map(c => `<option value="${c.id}">${c.full_name}</option>`).join('');
            }
        });
        const templateSelects = ['filterTemplate', 'docTemplate'];
        templateSelects.forEach(id => {
            const sel = document.getElementById(id);
            if (sel) {
                sel.innerHTML = '<option value="">Выберите...</option>' + 
                    templates.map(t => `<option value="${t.id}" data-requires-file="${t.requires_file}">${t.name}</option>`).join('');
            }
        });
    } catch (e) {
        showToast('Ошибка загрузки данных: ' + e.message, 'danger');
    }
}

async function loadDocuments() {
    const studentId = document.getElementById('filterStudent').value;
    const status = document.getElementById('filterStatus').value;
    const templateId = document.getElementById('filterTemplate').value;

    showLoading(true);
    try {
        let params = {};
        if (studentId) params.student_id = studentId;
        if (status) params.status = status;
        // Шаблон фильтруем на клиенте, если API не поддерживает
        allDocuments = await apiCall('parent.getDocuments', params);
        renderDocuments(allDocuments, templateId);
    } catch (e) {
        showToast('Ошибка загрузки документов: ' + e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function renderDocuments(docs, templateFilter) {
    const tbody = document.getElementById('documentsTableBody');
    let filtered = docs;
    if (templateFilter) {
        filtered = docs.filter(d => d.template_id == templateFilter);
    }
    if (!filtered || filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Нет документов</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map(d => {
        const statusClass = {
            'pending': 'warning',
            'approved': 'success',
            'rejected': 'danger',
            'expired': 'secondary'
        }[d.status] || 'secondary';
        return `
            <tr>
                <td>${d.student_name || '—'}</td>
                <td>${d.template_name || 'Без шаблона'}</td>
                <td><span class="badge bg-${statusClass}">${d.status}</span></td>
                <td>${d.expiry_date || '—'}</td>
                <td>${new Date(d.created_at).toLocaleDateString()}</td>
                <td>
                    ${d.file_path ? `<a href="/api.php?method=document.download&id=${d.id}" class="btn btn-sm btn-outline-primary" target="_blank">Скачать</a>` : '—'}
                </td>
            </tr>
        `;
    }).join('');
}

function setupEventListeners() {
    document.getElementById('applyDocFilters')?.addEventListener('click', loadDocuments);

    // Обработка формы загрузки
    document.getElementById('uploadDocumentForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const studentId = document.getElementById('docStudent').value;
        const templateId = document.getElementById('docTemplate').value;
        const fileInput = document.getElementById('docFile');
        const signatureCheck = document.getElementById('docSignature');
        const expiryDate = document.getElementById('docExpiry').value;

        if (!studentId) { showToast('Выберите ученика', 'warning'); return; }
        if (!templateId) { showToast('Выберите шаблон', 'warning'); return; }

        // Определяем, требуется ли файл
        const selectedTemplate = templates.find(t => t.id == templateId);
        const requiresFile = selectedTemplate ? selectedTemplate.requires_file : true;

        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('template_id', templateId);
        if (expiryDate) formData.append('expiry_date', expiryDate);

        if (requiresFile) {
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Выберите файл', 'warning');
                return;
            }
            formData.append('file', fileInput.files[0]);
        } else {
            if (!signatureCheck.checked) {
                showToast('Необходимо подтвердить согласие', 'warning');
                return;
            }
            formData.append('signature', 'true');
        }

        showLoading(true);
        try {
            const token = localStorage.getItem('jwt');
            const response = await fetch('/api.php?method=parent.uploadDocument', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                body: formData
            });
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Ошибка загрузки');
            }
            showToast('Документ загружен', 'success');
            bootstrap.Modal.getInstance(document.getElementById('uploadDocumentModal')).hide();
            this.reset();
            await loadDocuments();
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // При выборе шаблона показываем/скрываем поле файла и подписи
    document.getElementById('docTemplate')?.addEventListener('change', function() {
        const selected = templates.find(t => t.id == this.value);
        const requiresFile = selected ? selected.requires_file : true;
        document.getElementById('docFileContainer').classList.toggle('d-none', !requiresFile);
        document.getElementById('docSignatureContainer').classList.toggle('d-none', requiresFile);
    });
}
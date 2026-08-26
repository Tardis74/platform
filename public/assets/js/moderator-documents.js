let allDocuments = [];
let currentFilters = { student: '', template: '' };

/**
 * Загружает список документов на проверке.
 */
async function loadDocuments() {
    showLoading(true);
    try {
        allDocuments = await apiCall('moderator.getPendingDocuments');
        renderDocuments();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Рендерит таблицу с учётом фильтров.
 */
function renderDocuments() {
    const tbody = document.getElementById('documents-body');
    const filtered = allDocuments.filter(doc => {
        const studentMatch = doc.student_name.toLowerCase().includes(currentFilters.student.toLowerCase());
        const templateMatch = doc.template_name ? doc.template_name.toLowerCase().includes(currentFilters.template.toLowerCase()) : true;
        return studentMatch && templateMatch;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Нет документов на проверке</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(doc => `
        <tr>
            <td>${doc.student_name}</td>
            <td>${doc.class_name || ''}</td>
            <td>${doc.template_name || ''}</td>
            <td>${new Date(doc.created_at).toLocaleString()}</td>
            <td>${doc.file_url ? `<a href="${doc.file_url}" target="_blank">Скачать</a>` : ''}</td>
            <td>
                <button class="btn btn-sm btn-success approve-doc" data-id="${doc.id}">Подтвердить</button>
                <button class="btn btn-sm btn-danger reject-doc" data-id="${doc.id}" data-bs-toggle="modal" data-bs-target="#rejectModal">Отклонить</button>
            </td>
        </tr>
    `).join('');

    // Обработчики кнопок
    document.querySelectorAll('.approve-doc').forEach(btn => {
        btn.addEventListener('click', function() {
            confirmDocument(this.dataset.id);
        });
    });
    document.querySelectorAll('.reject-doc').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reject-document-id').value = this.dataset.id;
            document.getElementById('reject-comment').value = '';
        });
    });
}

/**
 * Подтверждение документа.
 */
async function confirmDocument(id) {
    showLoading(true);
    try {
        await apiCall('moderator.confirmDocument', { document_id: id });
        showToast('Документ подтверждён', 'success');
        await loadDocuments();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Отклонение документа (вызывается из модалки).
 */
async function rejectDocument() {
    const id = document.getElementById('reject-document-id').value;
    const comment = document.getElementById('reject-comment').value.trim();
    if (!comment) {
        showToast('Укажите причину отклонения', 'warning');
        return;
    }
    showLoading(true);
    try {
        await apiCall('moderator.rejectDocument', { document_id: id, comment });
        showToast('Документ отклонён', 'success');
        $('#rejectModal').modal('hide');
        await loadDocuments();
    } catch (error) {
        showToast(error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Инициализация страницы.
 */
function initPage() {
    // Фильтры
    document.getElementById('filter-student').addEventListener('input', function() {
        currentFilters.student = this.value;
        renderDocuments();
    });
    document.getElementById('filter-template').addEventListener('input', function() {
        currentFilters.template = this.value;
        renderDocuments();
    });
    document.getElementById('clear-filters').addEventListener('click', function() {
        document.getElementById('filter-student').value = '';
        document.getElementById('filter-template').value = '';
        currentFilters.student = '';
        currentFilters.template = '';
        renderDocuments();
    });

    document.getElementById('confirm-reject').addEventListener('click', rejectDocument);

    loadDocuments();
}
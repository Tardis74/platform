// assets/js/admin-classes.js
console.log('admin-classes.js loaded');

async function loadYears() {
    showLoading(true);
    try {
        const years = await apiCall('admin.academicYearList');
        const tbody = document.getElementById('years-tbody');
        if (!tbody) return;
        tbody.innerHTML = years.map(y => `
            <tr>
                <td>${y.name}</td>
                <td>${y.start_date}</td>
                <td>${y.end_date}</td>
                <td>${y.is_current ? 'Текущий' : 'Архивный'}</td>
                <td>
                    <button class="btn btn-sm btn-warning edit-year" data-id="${y.id}">Редактировать</button>
                    <button class="btn btn-sm btn-secondary archive-year" data-id="${y.id}">Архивировать</button>
                </td>
            </tr>
        `).join('');
        populateSelects(years);
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

function populateSelects(years) {
    const selects = document.querySelectorAll('#createClassForm select[name="academic_year_id"], #transferForm select, #viewClassModal select');
    selects.forEach(sel => {
        sel.innerHTML = years.map(y => `<option value="${y.id}">${y.name}</option>`).join('');
    });
}

async function loadClasses() {
    showLoading(true);
    try {
        const classes = await apiCall('admin.classList');
        const tbody = document.getElementById('classes-tbody');
        if (!tbody) return;
        tbody.innerHTML = classes.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>${c.academic_year_name || '—'}</td>
                <td>${c.teacher_name || '—'}</td>
                <td>${c.student_count || 0}</td>
                <td>
                    <button class="btn btn-sm btn-info view-class" data-id="${c.id}">Просмотр</button>
                    <button class="btn btn-sm btn-warning edit-class" data-id="${c.id}">Редактировать</button>
                    <button class="btn btn-sm btn-secondary archive-class" data-id="${c.id}">Архивировать</button>
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
    loadYears();
    loadClasses();

    // Создание года
    document.getElementById('createYearForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.academicYearCreate', data);
            showToast('Учебный год создан', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createYearModal'))?.hide();
            loadYears();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Создание класса
    document.getElementById('createClassForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.classCreate', data);
            showToast('Класс создан', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createClassModal'))?.hide();
            loadClasses();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Перевод учеников
    document.getElementById('transferForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.academicYearTransfer', data);
            showToast('Ученики переведены', 'success');
            bootstrap.Modal.getInstance(document.getElementById('transferModal'))?.hide();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Просмотр учеников класса
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-class')) {
            const id = e.target.dataset.id;
            apiCall('admin.classStudents', { class_id: id }).then(students => {
                const tbody = document.querySelector('#classStudentsTable tbody');
                if (!tbody) return;
                tbody.innerHTML = students.map(s => `
                    <tr>
                        <td>${s.full_name}</td>
                        <td>${s.status}</td>
                        <td>
                            <button class="btn btn-sm btn-success transfer-student" data-student="${s.id}" data-class="${id}">Переведён</button>
                            <button class="btn btn-sm btn-warning repeat-student" data-student="${s.id}">Оставлен на второй год</button>
                            <button class="btn btn-sm btn-danger left-student" data-student="${s.id}">Выбыл</button>
                            <button class="btn btn-sm btn-info arrived-student" data-student="${s.id}">Прибыл</button>
                        </td>
                    </tr>
                `).join('');
                new bootstrap.Modal(document.getElementById('viewClassModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });
    // Редактирование класса
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-class')) {
            const id = e.target.dataset.id;
            // Сначала загружаем список учебных годов для селектов
            apiCall('admin.academicYearList').then(years => {
                // Заполняем селекты в модалке редактирования
                const editSelects = document.querySelectorAll('#editClassForm select[name="academic_year_id"], #editClassForm select[name="teacher_id"]');
                // Для academic_year_id заполняем годами
                const yearSelect = document.querySelector('#editClassForm select[name="academic_year_id"]');
                if (yearSelect) {
                    yearSelect.innerHTML = years.map(y => `<option value="${y.id}">${y.name}</option>`).join('');
                }
                // Загружаем данные класса
                return apiCall('admin.classGet', { id });
            }).then(classData => {
                const form = document.getElementById('editClassForm');
                if (!form) return;
                form.elements['id'].value = classData.id;
                form.elements['name'].value = classData.name;
                form.elements['academic_year_id'].value = classData.academic_year_id || '';
                // Для teacher_id нужно загрузить список учителей — пока заглушка, можно оставить пустым
                // если есть метод admin.teacherList, можно его использовать
                new bootstrap.Modal(document.getElementById('editClassModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });

    // Сохранение редактирования класса
    document.getElementById('editClassForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.classUpdate', data);
            showToast('Класс обновлён', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editClassModal'))?.hide();
            loadClasses();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });

    // Редактирование учебного года
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-year')) {
            const id = e.target.dataset.id;
            apiCall('admin.academicYearGet', { id }).then(yearData => {
                const form = document.getElementById('editYearForm');
                if (!form) return;
                form.elements['id'].value = yearData.id;
                form.elements['name'].value = yearData.name;
                form.elements['start_date'].value = yearData.start_date;
                form.elements['end_date'].value = yearData.end_date;
                form.elements['is_current'].checked = yearData.is_current == 1;
                new bootstrap.Modal(document.getElementById('editYearModal')).show();
            }).catch(err => showToast(err.message, 'danger'));
        }
    });

    // Сохранение редактирования учебного года
    document.getElementById('editYearForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        data.is_current = data.is_current ? 1 : 0;
        try {
            await apiCall('admin.academicYearUpdate', data);
            showToast('Учебный год обновлён', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editYearModal'))?.hide();
            loadYears();
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });
});
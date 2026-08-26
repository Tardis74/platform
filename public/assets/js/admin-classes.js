// assets/js/admin-classes.js
async function loadYears() {
    try {
        const years = await apiCall('admin.academicYear.list');
        const tbody = document.getElementById('years-tbody');
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
        // Заполняем выпадающие списки в модалках
        populateSelects(years);
    } catch (e) { showToast(e.message, 'danger'); }
}

function populateSelects(years) {
    const selects = document.querySelectorAll('#createClassForm select[name="academic_year_id"], #transferForm select, #viewClassModal select');
    selects.forEach(sel => {
        sel.innerHTML = years.map(y => `<option value="${y.id}">${y.name}</option>`).join('');
    });
}

async function loadClasses() {
    try {
        const classes = await apiCall('admin.class.list');
        const tbody = document.getElementById('classes-tbody');
        tbody.innerHTML = classes.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>${c.academic_year_name}</td>
                <td>${c.teacher_name || '—'}</td>
                <td>${c.student_count}</td>
                <td>
                    <button class="btn btn-sm btn-info view-class" data-id="${c.id}">Просмотр</button>
                    <button class="btn btn-sm btn-warning edit-class" data-id="${c.id}">Редактировать</button>
                    <button class="btn btn-sm btn-secondary archive-class" data-id="${c.id}">Архивировать</button>
                </td>
            </tr>
        `).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

function initPage() {
    loadYears();
    loadClasses();

    // Создание года
    document.getElementById('createYearForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.academicYear.create', data);
            showToast('Учебный год создан', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createYearModal')).hide();
            loadYears();
        } catch (err) { showToast(err.message, 'danger'); }
    });

    // Создание класса
    document.getElementById('createClassForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.class.create', data);
            showToast('Класс создан', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createClassModal')).hide();
            loadClasses();
        } catch (err) { showToast(err.message, 'danger'); }
    });

    // Перевод учеников
    document.getElementById('transferForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        try {
            await apiCall('admin.academicYear.transfer', data);
            showToast('Ученики переведены', 'success');
            bootstrap.Modal.getInstance(document.getElementById('transferModal')).hide();
        } catch (err) { showToast(err.message, 'danger'); }
    });

    // Просмотр учеников класса
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-class')) {
            const id = e.target.dataset.id;
            apiCall('admin.class.students', { class_id: id }).then(students => {
                const tbody = document.querySelector('#classStudentsTable tbody');
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
}
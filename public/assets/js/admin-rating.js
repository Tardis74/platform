// assets/js/admin-rating.js
async function loadRatingClasses() {
    const classes = await apiCall('admin.class.list');
    const sel = document.getElementById('ratingClasses');
    sel.innerHTML = classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
}

async function loadRatingCategories() {
    const cats = await apiCall('admin.category.list');
    const sel = document.getElementById('ratingCategories');
    sel.innerHTML = cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
}

function initPage() {
    loadRatingClasses();
    loadRatingCategories();
    // Поиск ученика и показ портфолио
    document.getElementById('findStudent').addEventListener('click', async function() {
        const query = document.getElementById('studentSearch').value.trim();
        if (!query) return;
        try {
            const student = await apiCall('admin.student.find', { query });
            const achievements = await apiCall('admin.student.achievements', { student_id: student.id });
            let html = `<h6>${student.full_name} (${student.class_name})</h6>`;
            html += achievements.map(a => `
                <div class="card mb-2">
                    <div class="card-body">
                        <h6>${a.title}</h6>
                        <p>Категория: ${a.category_name}, баллы: ${a.weight}</p>
                        <p>Статус: ${a.status}</p>
                        <button class="btn btn-sm btn-success confirm-achievement" data-id="${a.id}">Подтвердить</button>
                        <button class="btn btn-sm btn-danger reject-achievement" data-id="${a.id}">Отклонить</button>
                    </div>
                </div>
            `).join('');
            document.getElementById('studentPortfolio').innerHTML = html;
        } catch (e) { showToast(e.message, 'danger'); }
    });
    // Подтверждение/отклонение достижений
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('confirm-achievement')) {
            const id = e.target.dataset.id;
            if (confirm('Подтвердить достижение?')) {
                apiCall('admin.achievement.confirm', { achievement_id: id }).then(() => {
                    showToast('Достижение подтверждено', 'success');
                    document.getElementById('findStudent').click();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
        if (e.target.classList.contains('reject-achievement')) {
            const id = e.target.dataset.id;
            const reason = prompt('Причина отклонения:');
            if (reason !== null) {
                apiCall('admin.achievement.reject', { achievement_id: id, reason }).then(() => {
                    showToast('Достижение отклонено', 'success');
                    document.getElementById('findStudent').click();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });
    // Построение рейтинга
    document.getElementById('buildRating').addEventListener('click', async function() {
        const period = document.getElementById('ratingPeriod').value;
        const classIds = Array.from(document.getElementById('ratingClasses').selectedOptions).map(o => o.value);
        const catIds = Array.from(document.getElementById('ratingCategories').selectedOptions).map(o => o.value);
        try {
            const rating = await apiCall('admin.rating.build', { period, class_ids: classIds, category_ids: catIds });
            const tbody = document.getElementById('ratingTbody');
            tbody.innerHTML = rating.items.map((item, idx) => `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${item.identifier}</td>
                    <td><input type="text" class="form-control rating-comment" data-student="${item.student_id}" value="${item.comment || ''}"></td>
                </tr>
            `).join('');
            document.getElementById('ratingPreview').style.display = 'block';
            window.currentRatingId = rating.id;
        } catch (e) { showToast(e.message, 'danger'); }
    });
    // Публикация
    document.getElementById('publishRating').addEventListener('click', async function() {
        const comments = {};
        document.querySelectorAll('.rating-comment').forEach(inp => {
            comments[inp.dataset.student] = inp.value;
        });
        try {
            await apiCall('admin.rating.publish', { rating_id: window.currentRatingId, comments });
            showToast('Рейтинг опубликован', 'success');
        } catch (e) { showToast(e.message, 'danger'); }
    });
    document.getElementById('unpublishRating').addEventListener('click', async function() {
        try {
            await apiCall('admin.rating.unpublish', { rating_id: window.currentRatingId });
            showToast('Публикация снята', 'success');
        } catch (e) { showToast(e.message, 'danger'); }
    });
    // Видимость места
    document.getElementById('showPlace').addEventListener('change', function() {
        apiCall('admin.rating.setVisibility', { show: this.checked }).catch(err => showToast(err.message, 'danger'));
    });
}
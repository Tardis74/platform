// assets/js/admin-rating.js
console.log('admin-rating.js loaded');

async function loadRatingClasses() {
    try {
        const classes = await apiCall('admin.classList');
        const sel = document.getElementById('ratingClasses');
        if (!sel) return;
        sel.innerHTML = classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

async function loadRatingCategories() {
    try {
        const cats = await apiCall('admin.categoryList');
        const sel = document.getElementById('ratingCategories');
        if (!sel) return;
        sel.innerHTML = cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadRatingClasses();
    loadRatingCategories();

    // Поиск ученика
    document.getElementById('findStudent')?.addEventListener('click', async function() {
        const query = document.getElementById('studentSearch')?.value.trim();
        if (!query) return;
        showLoading(true);
        try {
            const student = await apiCall('admin.studentFind', { query });
            const achievements = await apiCall('admin.studentAchievements', { student_id: student.id });
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
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Подтверждение/отклонение достижений
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('confirm-achievement')) {
            const id = e.target.dataset.id;
            if (confirm('Подтвердить достижение?')) {
                apiCall('admin.achievementConfirm', { achievement_id: id }).then(() => {
                    showToast('Достижение подтверждено', 'success');
                    document.getElementById('findStudent')?.click();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
        if (e.target.classList.contains('reject-achievement')) {
            const id = e.target.dataset.id;
            const reason = prompt('Причина отклонения:');
            if (reason !== null) {
                apiCall('admin.achievementReject', { achievement_id: id, reason }).then(() => {
                    showToast('Достижение отклонено', 'success');
                    document.getElementById('findStudent')?.click();
                }).catch(err => showToast(err.message, 'danger'));
            }
        }
    });

    // Построение рейтинга
    document.getElementById('buildRating')?.addEventListener('click', async function() {
        const period = document.getElementById('ratingPeriod')?.value;
        const classIds = Array.from(document.getElementById('ratingClasses')?.selectedOptions || []).map(o => o.value);
        const catIds = Array.from(document.getElementById('ratingCategories')?.selectedOptions || []).map(o => o.value);
        if (!period) { showToast('Выберите период', 'warning'); return; }
        showLoading(true);
        try {
            const rating = await apiCall('admin.ratingBuild', { period, class_ids: classIds, category_ids: catIds });
            const tbody = document.getElementById('ratingTbody');
            if (!tbody) return;
            tbody.innerHTML = rating.items.map((item, idx) => `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${item.identifier}</td>
                    <td><input type="text" class="form-control rating-comment" data-student="${item.student_id}" value="${item.comment || ''}"></td>
                </tr>
            `).join('');
            document.getElementById('ratingPreview').style.display = 'block';
            window.currentRatingId = rating.id;
        } catch (e) {
            showToast(e.message, 'danger');
        } finally {
            showLoading(false);
        }
    });

    // Публикация
    document.getElementById('publishRating')?.addEventListener('click', async function() {
        const comments = {};
        document.querySelectorAll('.rating-comment').forEach(inp => {
            comments[inp.dataset.student] = inp.value;
        });
        try {
            await apiCall('admin.ratingPublish', { rating_id: window.currentRatingId, comments });
            showToast('Рейтинг опубликован', 'success');
        } catch (e) { showToast(e.message, 'danger'); }
    });

    document.getElementById('unpublishRating')?.addEventListener('click', async function() {
        try {
            await apiCall('admin.ratingUnpublish', { rating_id: window.currentRatingId });
            showToast('Публикация снята', 'success');
        } catch (e) { showToast(e.message, 'danger'); }
    });

    // Видимость места
    document.getElementById('showPlace')?.addEventListener('change', function() {
        apiCall('admin.ratingSetVisibility', { show: this.checked }).catch(err => showToast(err.message, 'danger'));
    });
});
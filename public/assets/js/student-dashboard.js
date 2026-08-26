/**
 * Дашборд ученика.
 */
async function initPage() {
    try {
        const profile = await apiCall('student.profile');
        // Приветствие
        document.getElementById('student-name').textContent = profile.full_name || 'Ученик';
        document.getElementById('student-class').textContent = profile.class_name || 'Класс не указан';

        // Загружаем мероприятия (календарь)
        await loadCalendar();

        // Если проживает в общежитии – показываем виджет заявлений
        if (profile.is_dormitory) {
            document.getElementById('leave-widget').style.display = 'block';
            await loadLeaveRequests();
        } else {
            document.getElementById('leave-widget').style.display = 'none';
        }

        // Загружаем портфолио-виджет
        await loadPortfolioWidget(profile.total_points);
    } catch (e) {
        showToast('Ошибка загрузки дашборда: ' + e.message, 'danger');
    }
}

async function loadCalendar() {
    const container = document.getElementById('calendar-list');
    try {
        const events = await apiCall('student.getEvents');
        if (!events || events.length === 0) {
            container.innerHTML = '<p class="text-muted">Нет предстоящих мероприятий.</p>';
            return;
        }
        // Отображаем только ближайшие (например, первые 5)
        const upcoming = events.slice(0, 5);
        let html = '<ul class="list-unstyled">';
        upcoming.forEach(e => {
            const statusMap = {
                'pending': 'Ожидает',
                'approved': 'Подтверждено',
                'rejected': 'Отклонено',
                'cancelled': 'Отменено'
            };
            html += `
                <li class="mb-2">
                    <strong>${e.title}</strong><br>
                    <small>${new Date(e.start_datetime).toLocaleString()}</small><br>
                    <span class="badge bg-${e.status === 'approved' ? 'success' : e.status === 'pending' ? 'warning' : 'secondary'}">
                        ${statusMap[e.status] || e.status}
                    </span>
                </li>
            `;
        });
        html += '</ul>';
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<p class="text-muted">Не удалось загрузить мероприятия.</p>';
    }
}

async function loadLeaveRequests() {
    const container = document.getElementById('leave-list');
    try {
        const requests = await apiCall('student.getLeaveRequests');
        // Показываем только активные (pending, approved, exited)
        const active = requests.filter(r => ['pending', 'approved', 'exited'].includes(r.status));
        if (active.length === 0) {
            container.innerHTML = '<p class="text-muted">Нет активных заявлений.</p>';
            return;
        }
        let html = '<ul class="list-unstyled">';
        active.forEach(r => {
            html += `
                <li class="mb-2">
                    <strong>Выход: ${new Date(r.start_time).toLocaleString()}</strong><br>
                    <span class="badge bg-${r.status === 'approved' ? 'success' : r.status === 'pending' ? 'warning' : 'primary'}">
                        ${r.status}
                    </span>
                    ${r.qr_code ? '<br><canvas class="qr-code-canvas" data-qr="' + r.qr_code + '" width="80" height="80"></canvas>' : ''}
                </li>
            `;
        });
        html += '</ul>';
        container.innerHTML = html;
        // Генерируем QR-коды
        container.querySelectorAll('.qr-code-canvas').forEach(canvas => {
            const qrData = canvas.dataset.qr;
            if (qrData && typeof QRCode !== 'undefined') {
                new QRCode(canvas, {
                    text: qrData,
                    width: 80,
                    height: 80,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    } catch (e) {
        container.innerHTML = '<p class="text-muted">Не удалось загрузить заявления.</p>';
    }
}

async function loadPortfolioWidget(totalPoints) {
    document.getElementById('total-points').textContent = totalPoints || 0;
    // Место в рейтинге
    try {
        const ratingData = await apiCall('student.getRatingPlace');
        document.getElementById('rating-place').textContent = ratingData.place !== null ? ratingData.place : '—';
    } catch {
        document.getElementById('rating-place').textContent = '—';
    }

    // Последние достижения
    try {
        const achievements = await apiCall('student.getAchievements', { limit: 3 });
        const container = document.getElementById('recent-achievements');
        if (!achievements || achievements.length === 0) {
            container.innerHTML = '<p class="text-muted">Нет достижений.</p>';
            return;
        }
        let html = '<ul class="list-unstyled">';
        achievements.forEach(a => {
            html += `<li>${a.title} <span class="badge bg-secondary">${a.status}</span></li>`;
        });
        html += '</ul>';
        container.innerHTML = html;
    } catch {
        // игнорируем
    }
}
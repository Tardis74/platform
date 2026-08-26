// assets/js/admin-permissions.js
let selectedUserId = null;

function initPage() {
    // Поиск пользователя
    document.getElementById('permUserSearch').addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) return;
        apiCall('admin.user.search', { query }).then(users => {
            const sugg = document.getElementById('userSuggestions');
            sugg.innerHTML = users.map(u => `
                <button class="list-group-item list-group-item-action" data-id="${u.id}">${u.full_name} (${u.email})</button>
            `).join('');
            sugg.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('permUserSearch').value = this.textContent;
                    document.getElementById('selectedUserId').value = this.dataset.id;
                    selectedUserId = this.dataset.id;
                    sugg.innerHTML = '';
                    loadPermissions(selectedUserId);
                });
            });
        }).catch(() => {});
    });
    // Переключение режимов
    document.querySelectorAll('input[name="permMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('roleMode').style.display = this.value === 'role' ? 'block' : 'none';
            document.getElementById('customMode').style.display = this.value === 'custom' ? 'block' : 'none';
            if (this.value === 'custom' && selectedUserId) {
                loadCustomPermissions(selectedUserId);
            }
        });
    });
    // Применить роль
    document.getElementById('applyRole').addEventListener('click', async function() {
        if (!selectedUserId) { showToast('Выберите пользователя', 'warning'); return; }
        const role = document.getElementById('roleSelect').value;
        try {
            await apiCall('admin.permissions.set', { user_id: selectedUserId, role });
            showToast('Роль назначена', 'success');
        } catch (e) { showToast(e.message, 'danger'); }
    });
    // Сохранить персональные права
    document.getElementById('savePermissions').addEventListener('click', async function() {
        if (!selectedUserId) { showToast('Выберите пользователя', 'warning'); return; }
        const permissions = Array.from(document.querySelectorAll('#permissionsTree input:checked')).map(el => el.value);
        if (permissions.length === 0) { showToast('Выберите хотя бы одно разрешение', 'warning'); return; }
        try {
            await apiCall('admin.permissions.set', { user_id: selectedUserId, permissions });
            showToast('Права сохранены', 'success');
        } catch (e) { showToast(e.message, 'danger'); }
    });
    // Поиск по разрешениям
    document.getElementById('permSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#permissionsTree .permission-item').forEach(el => {
            const text = el.textContent.toLowerCase();
            el.style.display = text.includes(q) ? '' : 'none';
        });
    });
}

async function loadPermissions(userId) {
    const data = await apiCall('admin.permissions.get', { user_id: userId });
    if (data.role) {
        document.querySelector('input[name="permMode"][value="role"]').checked = true;
        document.getElementById('roleMode').style.display = 'block';
        document.getElementById('customMode').style.display = 'none';
        document.getElementById('roleSelect').value = data.role;
    } else {
        document.querySelector('input[name="permMode"][value="custom"]').checked = true;
        document.getElementById('roleMode').style.display = 'none';
        document.getElementById('customMode').style.display = 'block';
        loadCustomPermissions(userId);
    }
}

async function loadCustomPermissions(userId) {
    const allPerms = await apiCall('admin.permissions.listAll');
    const userPerms = await apiCall('admin.permissions.get', { user_id: userId });
    const userPermIds = userPerms.permissions || [];
    const tree = document.getElementById('permissionsTree');
    tree.innerHTML = '';
    const groups = {};
    allPerms.forEach(p => {
        const group = p.group || 'Прочее';
        if (!groups[group]) groups[group] = [];
        groups[group].push(p);
    });
    for (let [group, perms] of Object.entries(groups)) {
        const div = document.createElement('div');
        div.innerHTML = `<h6>${group}</h6>`;
        perms.forEach(p => {
            const checked = userPermIds.includes(p.id) ? 'checked' : '';
            div.innerHTML += `
                <div class="form-check permission-item">
                    <input class="form-check-input" type="checkbox" value="${p.id}" ${checked}>
                    <label class="form-check-label">${p.name}</label>
                </div>
            `;
        });
        tree.appendChild(div);
    }
}
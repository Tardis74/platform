// assets/js/admin-permissions.js
console.log('admin-permissions.js loaded');

let selectedUserId = null;

async function loadPermissions(userId) {
    showLoading(true);
    try {
        const data = await apiCall('admin.permissionsGet', { user_id: userId });
        const infoDiv = document.getElementById('currentRightsDisplay');
        if (!infoDiv) return;
        if (data.role) {
            infoDiv.innerHTML = `<p>Роль: <strong>${data.role}</strong></p><p>Права по умолчанию: ${data.permissions.join(', ')}</p>`;
            document.querySelector('input[name="permMode"][value="standard"]').checked = true;
            document.getElementById('standardMode').style.display = 'block';
            document.getElementById('customMode').style.display = 'none';
            document.getElementById('roleSelect').value = data.role;
        } else {
            infoDiv.innerHTML = `<p>Персональные права: ${data.permissions.join(', ')}</p>`;
            document.querySelector('input[name="permMode"][value="custom"]').checked = true;
            document.getElementById('standardMode').style.display = 'none';
            document.getElementById('customMode').style.display = 'block';
            await loadCustomPermissions(userId);
        }
        document.getElementById('currentUserInfo').style.display = 'block';
    } catch (e) {
        showToast(e.message, 'danger');
    } finally {
        showLoading(false);
    }
}

async function loadCustomPermissions(userId) {
    try {
        const allPerms = await apiCall('admin.permissionsList');
        const userPerms = await apiCall('admin.permissionsGet', { user_id: userId });
        const userPermIds = userPerms.permissions || [];
        const tree = document.getElementById('permissionsTree');
        if (!tree) return;
        tree.innerHTML = '';
        for (let [group, perms] of Object.entries(allPerms)) {
            const div = document.createElement('div');
            div.innerHTML = `<h6>${group}</h6>`;
            perms.forEach(p => {
                const checked = userPermIds.includes(p.id) ? 'checked' : '';
                div.innerHTML += `
                    <div class="form-check permission-item">
                        <input class="form-check-input" type="checkbox" value="${p.id}" ${checked}>
                        <label class="form-check-label">${p.label}</label>
                    </div>
                `;
            });
            tree.appendChild(div);
        }
    } catch (e) {
        showToast(e.message, 'danger');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Поиск пользователя
    document.getElementById('permUserSearch')?.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) return;
        apiCall('admin.userSearch', { query }).then(users => {
            const sugg = document.getElementById('userSuggestions');
            if (!sugg) return;
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
            document.getElementById('standardMode').style.display = this.value === 'standard' ? 'block' : 'none';
            document.getElementById('customMode').style.display = this.value === 'custom' ? 'block' : 'none';
            if (this.value === 'custom' && selectedUserId) {
                loadCustomPermissions(selectedUserId);
            }
        });
    });

    // Применить роль
    document.getElementById('applyRole')?.addEventListener('click', async function() {
        if (!selectedUserId) { showToast('Выберите пользователя', 'warning'); return; }
        const role = document.getElementById('roleSelect').value;
        try {
            await apiCall('admin.permissionsSet', { user_id: selectedUserId, type: 'standard', role });
            showToast('Роль назначена', 'success');
            loadPermissions(selectedUserId);
        } catch (e) { showToast(e.message, 'danger'); }
    });

    // Сохранить персональные права
    document.getElementById('savePermissions')?.addEventListener('click', async function() {
        if (!selectedUserId) { showToast('Выберите пользователя', 'warning'); return; }
        const permissions = Array.from(document.querySelectorAll('#permissionsTree input:checked')).map(el => el.value);
        if (permissions.length === 0) { showToast('Выберите хотя бы одно разрешение', 'warning'); return; }
        try {
            await apiCall('admin.permissionsSet', { user_id: selectedUserId, type: 'custom', permissions });
            showToast('Права сохранены', 'success');
            loadPermissions(selectedUserId);
        } catch (e) { showToast(e.message, 'danger'); }
    });

    // Поиск по разрешениям
    document.getElementById('permSearch')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#permissionsTree .permission-item').forEach(el => {
            const text = el.textContent.toLowerCase();
            el.style.display = text.includes(q) ? '' : 'none';
        });
    });
});
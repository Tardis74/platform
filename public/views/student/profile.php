<?php
/**
 * Профиль ученика и смена пароля.
 */
?>
<div id="student-profile">
    <h2>Мой профиль</h2>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Данные профиля</div>
                <div class="card-body">
                    <p><strong>ФИО:</strong> <span id="profile-fullname">—</span></p>
                    <p><strong>Класс:</strong> <span id="profile-class">—</span></p>
                    <p><strong>Email:</strong> <span id="profile-email">—</span></p>
                    <p><strong>Проживание:</strong> <span id="profile-dormitory">—</span></p>
                    <p><strong>Баллы:</strong> <span id="profile-points">0</span></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Смена пароля</div>
                <div class="card-body">
                    <?php if (isset($_GET['first_login']) && $_GET['first_login'] == 1): ?>
                        <div class="alert alert-warning">Необходимо сменить пароль.</div>
                    <?php endif; ?>
                    <form id="changePasswordForm">
                        <div class="mb-3" id="current-password-group">
                            <label for="currentPassword" class="form-label">Текущий пароль</label>
                            <input type="password" id="currentPassword" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Новый пароль</label>
                            <input type="password" id="newPassword" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Подтверждение пароля</label>
                            <input type="password" id="confirmPassword" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/student-profile.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initPage();
    });
</script>
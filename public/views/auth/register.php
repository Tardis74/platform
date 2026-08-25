<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <h2 class="text-center mb-4">Регистрация родителя</h2>
        <form id="register-form">
            <div class="mb-3">
                <label for="full_name" class="form-label">Полное имя</label>
                <input type="text" class="form-control" id="full_name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль (минимум 6 символов)</label>
                <input type="password" class="form-control" id="password" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                <input type="password" class="form-control" id="password_confirm" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="consent" required>
                <label class="form-check-label" for="consent">Я согласен на обработку персональных данных</label>
            </div>
            <button type="submit" class="btn btn-success w-100">Зарегистрироваться</button>
        </form>
        <div class="mt-3 text-center">
            <a href="/auth/login">Уже есть аккаунт? Войти</a>
        </div>
    </div>
</div>
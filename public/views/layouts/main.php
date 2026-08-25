<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лицей Платформа</title>
    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Кастомные стили -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="bg-primary text-white py-2">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <img src="/assets/images/logo.png" alt="Логотип" height="40" class="me-2">
                <span class="h4 mb-0">Лицей Платформа</span>
            </div>
            <div id="user-info" class="d-none">
                <span id="user-name"></span>
                <button class="btn btn-outline-light btn-sm ms-2" id="logout-btn">Выйти</button>
            </div>
        </div>
    </header>

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav" id="nav-menu">
                    <!-- Навигация будет заполняться динамически через JS -->
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-3">
        <?php if (isset($viewFile) && file_exists($viewFile)): ?>
            <?php include $viewFile; ?>
        <?php else: ?>
            <div class="alert alert-danger">Страница не найдена.</div>
        <?php endif; ?>
    </main>

    <footer class="bg-light text-center text-muted py-3 mt-5">
        <div class="container">
            &copy; 2026 Лицей. Все права защищены.
        </div>
    </footer>

    <!-- Bootstrap JS (для переключателей и т.д.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Глобальные скрипты -->
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/components.js"></script>
    <!-- Скрипты для страниц аутентификации -->
    <?php if (isset($role) && $role === 'auth'): ?>
        <script src="/assets/js/auth.js"></script>
    <?php endif; ?>
    <!-- Инициализация страницы (вызов initPage, если определена) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initPage === 'function') {
                initPage();
            }
        });
    </script>
</body>
</html>
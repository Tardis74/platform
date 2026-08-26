<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лицей – Личный кабинет</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- QRCode.js -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <!-- Глобальный CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Лицей</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($role === 'student'): ?>
                        <li class="nav-item"><a class="nav-link" href="/student/dashboard">Дашборд</a></li>
                        <li class="nav-item"><a class="nav-link" href="/student/events">Мероприятия</a></li>
                        <li class="nav-item"><a class="nav-link" href="/student/portfolio">Портфолио</a></li>
                        <li class="nav-item"><a class="nav-link" href="/student/leave" id="nav-leave">Заявления</a></li>
                        <li class="nav-item"><a class="nav-link" href="/student/profile">Профиль</a></li>
                    <?php elseif ($role === 'parent'): ?>
                        <!-- навигация для родителя (уже есть) -->
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text me-2" id="user-info">
                            <span id="user-name"></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm" id="logout-btn">Выйти</button>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <?php include $viewFile; ?>
    </main>

    <!-- Bootstrap JS и зависимости -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Глобальный JS -->
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/auth.js"></script>
    <!-- Инициализация -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Если определена функция initPage, вызываем её
            if (typeof initPage === 'function') {
                initPage();
            }
            // Настройка выхода
            document.getElementById('logout-btn')?.addEventListener('click', logout);
        });
    </script>
</body>
</html>
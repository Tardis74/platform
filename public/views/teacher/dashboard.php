<?php
$pageTitle = 'Панель классного руководителя';
$pageScript = '/assets/js/teacher-dashboard.js';
?>
<div class="container mt-4">
    <h1 class="mb-4">Панель классного руководителя</h1>
    <div id="teacher-dashboard">
        <!-- Приветствие -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title" id="greeting">Загрузка...</h5>
                        <p class="card-text" id="class-info">Класс: <span id="class-name">—</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Учеников</h5>
                        <p class="card-text display-6" id="stat-students">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Ожидают подтверждения</h5>
                        <p class="card-text display-6" id="stat-pending">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Заявлений на выход сегодня</h5>
                        <p class="card-text display-6" id="stat-leave">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Мероприятий на неделю</h5>
                        <p class="card-text display-6" id="stat-events">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Быстрые ссылки -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Быстрые ссылки</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="/teacher/confirm" class="btn btn-outline-primary">Подтверждение учеников</a>
                            <a href="/teacher/seating" class="btn btn-outline-primary">Рассадка столовой</a>
                            <a href="/teacher/attendance" class="btn btn-outline-primary">Отметки о питании</a>
                            <a href="/teacher/portfolio" class="btn btn-outline-primary">Портфолио класса</a>
                            <a href="/teacher/events" class="btn btn-outline-primary">Мероприятия</a>
                            <a href="/teacher/leave" class="btn btn-outline-primary">Заявления на выход</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
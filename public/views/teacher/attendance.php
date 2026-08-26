<?php
$pageTitle = 'Ежедневные отметки о питании';
$pageScript = '/assets/js/teacher-attendance.js';
?>
<div class="container mt-4">
    <h1 class="mb-4">Ежедневные отметки о питании</h1>
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="attendance-date" class="form-label">Дата</label>
                    <input type="date" class="form-control" id="attendance-date">
                </div>
                <div class="col-md-8 d-flex align-items-end gap-2">
                    <button class="btn btn-outline-secondary" id="mark-all">Отметить всех</button>
                    <button class="btn btn-outline-secondary" id="unmark-all">Снять все</button>
                    <button class="btn btn-primary" id="save-attendance">Сохранить</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="attendance-table">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Присутствовал</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-body">
                        <tr><td colspan="2" class="text-muted">Загрузка...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
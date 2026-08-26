<?php
$pageTitle = 'Управление рассадкой столовой';
$pageScript = '/assets/js/teacher-seating.js';
?>
<div class="container mt-4">
    <h1 class="mb-4">Управление рассадкой столовой</h1>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <button class="btn btn-success" id="save-seating">Сохранить</button>
                <button class="btn btn-danger" id="clear-seating">Очистить рассадку</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="seating-table">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Стол</th>
                            <th>Место</th>
                        </tr>
                    </thead>
                    <tbody id="seating-body">
                        <tr><td colspan="3" class="text-muted">Загрузка...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
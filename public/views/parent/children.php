<?php
/**
 * Управление детьми – таблица со списком, модальные окна добавления и привязки.
 */
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>👨‍👩‍👧 Управление детьми</h4>
        <div>
            <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addChildModal">
                <i class="bi bi-plus-circle"></i> Добавить
            </button>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#linkChildModal">
                <i class="bi bi-link"></i> Привязать
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="childrenTable">
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Класс</th>
                    <th>Дата рождения</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="childrenTableBody">
                <!-- Загружается через JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Модальное окно: Добавить ребёнка (аналогично dashboard) -->
<div class="modal fade" id="addChildModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить ребёнка</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addChildForm">
                    <div class="mb-3">
                        <label for="child_snils" class="form-label">СНИЛС (11 цифр)</label>
                        <input type="text" class="form-control" id="child_snils" placeholder="12345678901" required pattern="\d{11}">
                    </div>
                    <div class="mb-3">
                        <label for="child_full_name" class="form-label">ФИО</label>
                        <input type="text" class="form-control" id="child_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="child_class" class="form-label">Класс</label>
                        <select class="form-select" id="child_class">
                            <option value="">Не выбран</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="child_birth_date" class="form-label">Дата рождения</label>
                        <input type="date" class="form-control" id="child_birth_date">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="child_is_dormitory">
                        <label class="form-check-label" for="child_is_dormitory">Проживает в общежитии</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно: Привязать существующего -->
<div class="modal fade" id="linkChildModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Привязать существующего ученика</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="linkChildForm">
                    <div class="mb-3">
                        <label for="link_snils" class="form-label">СНИЛС (11 цифр)</label>
                        <input type="text" class="form-control" id="link_snils" placeholder="12345678901" required pattern="\d{11}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Найти и привязать</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/parent-children.js"></script>
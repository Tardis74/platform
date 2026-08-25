#API Документация – Платформа Лицея
#Общая информация

Базовая точка входа: /api.php
Все запросы (кроме публичных) должны содержать заголовок: Authorization: Bearer <jwt_token>
Формат данных: JSON (кроме achievement.add, где используется multipart/form-data)
Ответы: всегда содержат поля success (bool), data (mixed) и error (string|null)
Коды ошибок: стандартные HTTP (400, 401, 403, 404, 409, 429, 500)

##Публичные методы (не требуют JWT)

###ping
Проверка доступности API.

Метод: GET
URL: /api.php?method=ping
Ответ:
{ "success": true, "data": "pong", "error": null }

###auth.register
Регистрация нового родителя.

Метод: POST
URL: /api.php?method=auth.register
Тело (JSON):
{
"full_name": "Иванов Иван Иванович",
"email": "parent@example.com",
"password": "secure_password",
"consent": true
}
Ответ (200):
{
"success": true,
"data": {
"token": "jwt_token",
"user": {
"id": 1,
"email": "parent@example.com",
"full_name": "Иванов Иван Иванович",
"role": "parent"
}
}
}
Ошибки: 400 – не все поля, 422 – согласие не передано, 409 – email занят.

###auth.login
Авторизация родителя или другого пользователя (кроме ученика).

Метод: POST
URL: /api.php?method=auth.login
Тело (JSON):
{
"email": "user@example.com",
"password": "password"
}
Ответ (200):
{
"success": true,
"data": {
"token": "jwt_token",
"user": {
"id": 1,
"email": "user@example.com",
"role": "admin"
}
}
}
Ошибки: 401 – неверные учётные данные.

###auth.refresh
Обновление JWT-токена (без смены пользователя).

Метод: POST
URL: /api.php?method=auth.refresh
Требуется токен в заголовке (для проверки).
Ответ (200):
{
"success": true,
"data": {
"token": "new_jwt_token"
}
}

###student.login
Вход ученика по временному паролю.

Метод: POST
URL: /api.php?method=student.login
Тело (JSON):
{
"email": "student@example.com",
"password": "temporary_password"
}
Ответ (200):
{
"success": true,
"data": {
"token": "jwt_token",
"user": {
"id": 1,
"email": "student@example.com",
"full_name": "Иванов Иван",
"role": "student"
},
"requires_password_change": true
}
}
Примечание: если это первый вход ученика, requires_password_change будет true.

##Методы, требующие JWT (все остальные)

Общие заголовки для всех защищённых методов
Authorization: Bearer <jwt_token>

##Авторизация и управление аккаунтом

###auth.check
Проверка валидности токена и получение данных пользователя.

Метод: GET
URL: /api.php?method=auth.check
Ответ (200):
{
"success": true,
"data": {
"user": {
"id": 1,
"email": "user@example.com",
"role": "admin"
}
}
}

###auth.logout
Выход (клиент удаляет токен).

Метод: POST
URL: /api.php?method=auth.logout
Ответ (200): { "success": true, "data": null, "error": null }

###student.changePassword
Смена пароля учеником (только для роли student).

Метод: POST
URL: /api.php?method=student.changePassword
Тело (JSON):
{
"current_password": "old_password",
"new_password": "new_password"
}
Ответ (200):
{
"success": true,
"data": {
"message": "Пароль изменён"
}
}
Ошибки: 400 – короткий пароль или не заполнены поля; 400 – неверный текущий пароль.

##Личный кабинет ученика

###student.profile
Получение данных профиля ученика.

Метод: GET
URL: /api.php?method=student.profile
Ответ (200):
{
"success": true,
"data": {
"id": 1,
"full_name": "Иванов Иван",
"class_name": "10А",
"total_points": 15,
"is_dormitory": false
}
}

###achievement.add
Загрузка нового достижения (файл + категория).

Метод: POST (multipart/form-data)
URL: /api.php?method=achievement.add
Поля формы:

category_id (int, обязательное)

title (string, обязательное)

description (string, опционально)

file (файл, обязательное; разрешены: jpg, jpeg, png, gif, pdf; макс. 10 МБ)
Ответ (200):
{
"success": true,
"data": {
"achievement_id": 1,
"status": "pending",
"message": "Достижение отправлено на проверку"
}
}
Ошибки: 404 – категория не найдена; 400 – неверный файл.

###achievement.list
Список достижений ученика с фильтрацией.

Метод: GET
URL: /api.php?method=achievement.list
Параметры URL (опционально):

category_id – ID категории

year – год (YYYY)
Ответ (200):
{
"success": true,
"data": [
{
"id": 1,
"title": "Победитель олимпиады",
"category_name": "Олимпиада",
"status": "approved",
"created_at": "2026-08-25 10:00:00",
"file_url": "/api.php?method=achievement.download&id=1"
}
]
}

###achievement.get
Детальная информация о конкретном достижении.

Метод: GET
URL: /api.php?method=achievement.get
Параметры URL:

id (int, обязательное) – ID достижения
Ответ (200):
{
"success": true,
"data": {
"id": 1,
"title": "Победитель олимпиады",
"description": "Муниципальный этап",
"category_name": "Олимпиада",
"status": "rejected",
"moderator_comment": "Недостаточно подтверждающих документов",
"file_url": "/api.php?method=achievement.download&id=1",
"created_at": "2026-08-25 10:00:00"
}
}

###achievement.download
Скачивание файла достижения (защищённая ссылка).

Метод: GET
URL: /api.php?method=achievement.download
Параметры URL:

id (int, обязательное) – ID достижения
Ответ: файл отдаётся напрямую (Content-Disposition: attachment). Доступ разрешён только владельцу-ученику, модератору или администратору.
Ошибки: 403 – доступ запрещён; 404 – файл не найден.

##Управление детьми (родитель)

###parent.getChildren
Получить список привязанных детей (родитель).

Метод: GET
URL: /api.php?method=parent.getChildren
Ответ (200):
{
"success": true,
"data": [
{
"id": 1,
"full_name": "Иванов Иван",
"class_name": "10А",
"status": "active",
"is_dormitory": false
}
]
}

###parent.addChild
Добавить ребёнка (создать нового или отправить заявку на привязку).

Метод: POST
URL: /api.php?method=parent.addChild
Тело (JSON):
{
"snils": "123-456-789 01",
"full_name": "Иванов Иван",
"class_id": 1, // опционально
"birth_date": "2008-01-01", // опционально
"is_dormitory": false // опционально
}
Ответ (200) – если ученик новый:
{
"success": true,
"data": {
"student_id": 1,
"temporary_password": "a1b2c3d4",
"status": "awaiting_confirmation",
"message": "Профиль создан. Передайте временный пароль ученику для входа."
}
}
Ответ (200) – если ученик уже существует:
{
"success": true,
"data": {
"student_id": 1,
"status": "pending",
"message": "Ученик уже зарегистрирован. Запрос на привязку отправлен."
}
}

###parent.linkChild
Привязка существующего ученика по СНИЛС.

Метод: POST
URL: /api.php?method=parent.linkChild
Тело (JSON):
{
"snils": "123-456-789 01"
}
Ответ (200):
{
"success": true,
"data": {
"student_id": 1,
"status": "pending",
"message": "Запрос на привязку отправлен."
}
}

##Подтверждение учеников (учитель)

###teacher.getPendingStudents
Получить учеников своего класса, ожидающих подтверждения.

Метод: GET
URL: /api.php?method=teacher.getPendingStudents
Ответ (200):
{
"success": true,
"data": [
{
"id": 1,
"full_name": "Иванов Иван",
"birth_date": "2008-01-01",
"snils_masked": "123***789",
"class_name": "10А",
"created_at": "2026-08-24 10:00:00"
}
]
}

###teacher.confirmStudent
Подтвердить ученика учителем.

Метод: POST
URL: /api.php?method=teacher.confirmStudent
Тело (JSON):
{
"student_id": 1
}
Ответ (200):
{
"success": true,
"data": {
"student_id": 1,
"status": "active",
"message": "Ученик подтверждён"
}
}

###teacher.rejectStudent
Отклонить ученика учителем с указанием причины.

Метод: POST
URL: /api.php?method=teacher.rejectStudent
Тело (JSON):
{
"student_id": 1,
"reason": "Не предоставлены документы"
}
Ответ (200):
{
"success": true,
"data": {
"student_id": 1,
"status": "rejected",
"message": "Ученик отклонён"
}
}

##Административные методы (админ/модератор)

###admin.getAllPendingStudents
Все ученики, ожидающие подтверждения (для администратора).

Метод: GET
URL: /api.php?method=admin.getAllPendingStudents
Ответ (200): аналогичен teacher.getPendingStudents, но без фильтра по классу.

###admin.confirmStudentByAdmin
Подтверждение ученика администратором или модератором.

Метод: POST
URL: /api.php?method=admin.confirmStudentByAdmin
Тело (JSON):
{
"student_id": 1
}
Ответ (200):
{
"success": true,
"data": {
"student_id": 1,
"status": "active",
"message": "Ученик подтверждён администратором"
}
}

###admin.rejectStudentByAdmin
Отклонение ученика администратором.

Метод: POST
URL: /api.php?method=admin.rejectStudentByAdmin
Тело (JSON):
{
"student_id": 1,
"reason": "Нарушение правил"
}
Ответ (200):
{
"success": true,
"data": {
"student_id": 1,
"status": "rejected",
"message": "Ученик отклонён администратором"
}
}

##Модерация достижений (модератор/администратор)

###moderator.getPendingAchievements
Список достижений на проверке.

Метод: GET
URL: /api.php?method=moderator.getPendingAchievements
Параметры URL (опционально):

student_id – фильтр по ученику
Ответ (200):
{
"success": true,
"data": [
{
"id": 2,
"student_name": "Петров Пётр",
"class_name": "10Б",
"category_name": "Спорт",
"title": "Победитель соревнований",
"created_at": "2026-08-25 11:00:00",
"file_url": "/api.php?method=achievement.download&id=2"
}
]
}

###moderator.confirmAchievement
Подтверждение достижения (статус approved, начисление баллов).

Метод: POST
URL: /api.php?method=moderator.confirmAchievement
Тело (JSON):
{
"achievement_id": 2,
"comment": "Принято" // опционально
}
Ответ (200):
{
"success": true,
"data": {
"achievement_id": 2,
"status": "approved",
"points_added": 5
}
}

###moderator.rejectAchievement
Отклонение достижения с обязательным комментарием.

Метод: POST
URL: /api.php?method=moderator.rejectAchievement
Тело (JSON):
{
"achievement_id": 3,
"comment": "Неверный формат файла"
}
Ответ (200):
{
"success": true,
"data": {
"achievement_id": 3,
"status": "rejected"
}
}

Коды ошибок (общие)

400 – Неверный запрос (не хватает полей, неверный формат и т.п.)
401 – Не авторизован (отсутствует или невалидный JWT)
403 – Доступ запрещён (недостаточно прав)
404 – Ресурс не найден
409 – Конфликт (уже существует, уже подтверждён и т.п.)
429 – Слишком много запросов (Rate Limiting)
500 – Внутренняя ошибка сервера

Примечания

Все временные пароли генерируются родителем при создании ученика и передаются ученику.

Ученик может просматривать только свои достижения; модератор/администратор – все.

Файлы достижений хранятся вне публичной директории и доступны только через защищённый метод achievement.download.

Логирование всех действий учеников и модераторов ведётся в storage/logs/portfolio.log.
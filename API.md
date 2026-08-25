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

##Мероприятия (создание, просмотр, запись)

###event.create
Создание нового мероприятия. Доступно для ролей admin, moderator, teacher.

Метод: POST
URL: /api.php?method=event.create
Тело (JSON):
{
"title": "Школьная олимпиада по математике",
"description": "Муниципальный этап",
"start_datetime": "2026-09-15 10:00:00",
"end_datetime": "2026-09-15 14:00:00",
"location": "Актовый зал",
"category_id": 1,
"max_participants": 50,
"points": 10,
"requires_confirmation": true,
"requires_documents": false,
"class_ids": [1, 2],
"dormitory_access": [true, false],
"tag_ids": [3, 5]
}

Параметры:

title (string, обязательное) – название мероприятия

description (string, опционально) – описание

start_datetime (string, обязательное) – дата и время начала в формате YYYY-MM-DD HH:MM:SS

end_datetime (string, опционально) – дата и время окончания

location (string, опционально) – место проведения

category_id (int, опционально) – ID категории

max_participants (int, опционально) – максимальное число участников (если не указано – безлимит)

points (int, по умолчанию 0) – баллы за участие

requires_confirmation (bool, по умолчанию true) – требуется ли подтверждение модератором

requires_documents (bool, по умолчанию false) – требуется ли загрузка документов

class_ids (массив int, опционально) – ID классов, которым доступно мероприятие. Если пусто – доступно всем

dormitory_access (массив bool, опционально) – массив значений true/false, определяющий доступность для проживающих в общежитии (true) и горожан (false). Если пусто – доступно всем

tag_ids (массив int, опционально) – ID тегов мероприятия

Ответ (200):
{
"success": true,
"data": {
"event_id": 1,
"message": "Мероприятие создано"
}
}

Ошибки: 400 – не заполнены обязательные поля или неверный формат даты; 403 – недостаточно прав.

###event.update
Обновление существующего мероприятия. Доступно создателю мероприятия или администратору. Мероприятие можно изменять только до его начала.

Метод: POST
URL: /api.php?method=event.update
Тело (JSON):
{
"event_id": 1,
"title": "Новое название",
"max_participants": 60,
"class_ids": [1, 2, 3]
}

Параметры:

event_id (int, обязательное) – ID мероприятия

Остальные поля – любые из event.create (передаются только изменяемые)

Ответ (200):
{
"success": true,
"data": {
"event_id": 1,
"message": "Мероприятие обновлено"
}
}

Ошибки: 400 – event_id не указан или невалидная дата; 403 – недостаточно прав; 404 – мероприятие не найдено.

###event.delete (отмена)
Отмена мероприятия (перевод в статус cancelled). Доступно создателю или администратору.

Метод: POST
URL: /api.php?method=event.delete
Тело (JSON):
{
"event_id": 1
}

Ответ (200):
{
"success": true,
"data": {
"message": "Мероприятие отменено"
}
}

###event.list
Получение списка мероприятий с фильтрацией. Для ученика возвращаются только доступные мероприятия (по классу и типу проживания), для родителей – мероприятия детей (используйте отдельный метод parent.getChildrenEvents). Для модератора/администратора – все.

Метод: GET
URL: /api.php?method=event.list
Параметры URL (опционально):

start_date – дата начала (включительно) в формате YYYY-MM-DD

end_date – дата окончания (включительно) в формате YYYY-MM-DD

category_id – ID категории

tag_id – ID тега

status – фильтр по статусу (active, cancelled, completed)

page – номер страницы (по умолчанию 1)

limit – количество записей на странице (по умолчанию 20, максимум 100)

Ответ (200):
{
"success": true,
"data": [
{
"id": 1,
"title": "Олимпиада по математике",
"start_datetime": "2026-09-15 10:00:00",
"end_datetime": "2026-09-15 14:00:00",
"location": "Актовый зал",
"category_name": "Олимпиады",
"max_participants": 50,
"current_count": 10,
"points": 10,
"status": "active",
"is_registered": false,
"registration_status": null
}
]
}

Для ученика дополнительно возвращаются поля:

is_registered – зарегистрирован ли текущий ученик на мероприятие

registration_status – статус его заявки (pending, approved, rejected, cancelled, completed), если есть

###event.get
Получение полной информации о мероприятии, включая списки классов, тегов и типов проживания. Для ученика также возвращается статус его заявки.

Метод: GET
URL: /api.php?method=event.get
Параметры URL:

event_id (int, обязательное) – ID мероприятия

Ответ (200):
{
"success": true,
"data": {
"id": 1,
"title": "Олимпиада по математике",
"description": "Муниципальный этап",
"start_datetime": "2026-09-15 10:00:00",
"end_datetime": "2026-09-15 14:00:00",
"location": "Актовый зал",
"category_id": 1,
"category_name": "Олимпиады",
"max_participants": 50,
"current_count": 10,
"points": 10,
"requires_confirmation": true,
"requires_documents": false,
"status": "active",
"created_by": 5,
"tags": [
{ "id": 3, "name": "Математика" },
{ "id": 5, "name": "Олимпиада" }
],
"class_access": [
{ "id": 1, "name": "10А" },
{ "id": 2, "name": "10Б" }
],
"dormitory_access": [true, false],
"registration_status": "pending"
}
}

###student.eventRegister
Подача заявки на мероприятие учеником.

Метод: POST
URL: /api.php?method=student.eventRegister
Тело (JSON):
{
"event_id": 1
}

Ответ (200):
{
"success": true,
"data": {
"registration_id": 1,
"status": "pending",
"message": "Заявка подана, ожидает подтверждения"
}
}

Если подтверждение не требуется (requires_confirmation = false), статус будет approved, а сообщение «Вы записаны на мероприятие».

Ошибки: 403 – мероприятие недоступно для ученика; 409 – уже зарегистрирован или нет свободных мест; 404 – мероприятие не найдено или неактивно.

###student.eventUnregister
Отмена заявки учеником (только для статусов pending или approved). После отмены место освобождается.

Метод: POST
URL: /api.php?method=student.eventUnregister
Тело (JSON):
{
"event_id": 1
}

Ответ (200):
{
"success": true,
"data": {
"message": "Заявка отменена"
}
}

###student.eventMyRegistrations
Список мероприятий, на которые ученик записан (включая черновики, ожидающие, подтверждённые и т.д.).

Метод: GET
URL: /api.php?method=student.eventMyRegistrations
Параметры URL (опционально):

status – фильтр по статусу заявки (draft, pending, approved, rejected, cancelled, completed)

start_date – дата начала мероприятия (включительно), формат YYYY-MM-DD

end_date – дата окончания мероприятия (включительно)

Ответ (200):
{
"success": true,
"data": [
{
"id": 1,
"event_id": 1,
"student_id": 1,
"status": "pending",
"comment": null,
"registered_at": "2026-08-25 10:00:00",
"updated_at": "2026-08-25 10:00:00",
"title": "Олимпиада по математике",
"start_datetime": "2026-09-15 10:00:00",
"end_datetime": "2026-09-15 14:00:00",
"location": "Актовый зал",
"event_status": "active"
}
]
}

###moderator.getPendingRegistrations
Получение списка заявок, ожидающих подтверждения (для ролей admin, moderator, teacher). Для учителя возвращаются только заявки учеников его класса.

Метод: GET
URL: /api.php?method=moderator.getPendingRegistrations
Параметры URL (опционально):

event_id – фильтр по мероприятию

student_id – фильтр по ученику

status – статус заявки (по умолчанию pending)

Ответ (200):
{
"success": true,
"data": [
{
"registration_id": 1,
"student_id": 1,
"student_name": "Иванов Иван",
"class_name": "10А",
"event_title": "Олимпиада по математике",
"status": "pending",
"comment": null,
"registered_at": "2026-08-25 10:00:00"
}
]
}

###moderator.confirmRegistration
Подтверждение заявки модератором/администратором/учителем (только для статуса pending).

Метод: POST
URL: /api.php?method=moderator.confirmRegistration
Тело (JSON):
{
"registration_id": 1
}

Ответ (200):
{
"success": true,
"data": {
"registration_id": 1,
"status": "approved"
}
}

###moderator.rejectRegistration
Отклонение заявки с указанием причины. Место освобождается.

Метод: POST
URL: /api.php?method=moderator.rejectRegistration
Тело (JSON):
{
"registration_id": 1,
"reason": "Не предоставлены необходимые документы"
}

Ответ (200):
{
"success": true,
"data": {
"registration_id": 1,
"status": "rejected"
}
}

###parent.getChildrenEvents
Получение мероприятий, на которые записаны дети родителя. Возвращает объединённый календарь с указанием ребёнка.

Метод: GET
URL: /api.php?method=parent.getChildrenEvents
Параметры URL (опционально):

child_id – ID конкретного ребёнка (если не указан – все дети)

start_date – дата начала (включительно), формат YYYY-MM-DD

end_date – дата окончания (включительно)

Ответ (200):
{
"success": true,
"data": [
{
"id": 1,
"event_id": 1,
"student_id": 1,
"student_name": "Иванов Иван",
"status": "approved",
"comment": null,
"registered_at": "2026-08-25 10:00:00",
"title": "Олимпиада по математике",
"start_datetime": "2026-09-15 10:00:00",
"end_datetime": "2026-09-15 14:00:00",
"location": "Актовый зал"
}
]
}

##Управление справочниками (категории и теги)

Доступно только для роли admin (методы создания, обновления, удаления). Просмотр списка доступен всем авторизованным пользователям.

###admin.categoryList
Получение списка всех категорий мероприятий.

Метод: GET
URL: /api.php?method=admin.categoryList
Ответ (200):
{
"success": true,
"data": [
{ "id": 1, "name": "Олимпиады", "created_at": "2026-08-25 10:00:00" },
{ "id": 2, "name": "Спортивные", "created_at": "2026-08-25 10:00:00" }
]
}

###admin.categoryCreate
Создание новой категории.

Метод: POST
URL: /api.php?method=admin.categoryCreate
Тело (JSON):
{
"name": "Творческие конкурсы"
}

Ответ (200):
{
"success": true,
"data": {
"category_id": 3,
"message": "Категория создана"
}
}

###admin.categoryUpdate
Обновление названия категории.

Метод: POST
URL: /api.php?method=admin.categoryUpdate
Тело (JSON):
{
"id": 1,
"name": "Интеллектуальные соревнования"
}

Ответ (200):
{
"success": true,
"data": {
"message": "Категория обновлена"
}
}

###admin.categoryDelete
Удаление категории (если она не используется в мероприятиях, иначе может возникнуть ошибка внешнего ключа).

Метод: POST
URL: /api.php?method=admin.categoryDelete
Тело (JSON):
{
"id": 3
}

Ответ (200):
{
"success": true,
"data": {
"message": "Категория удалена"
}
}

###admin.tagList
Получение списка всех тегов.

Метод: GET
URL: /api.php?method=admin.tagList
Ответ (200):
{
"success": true,
"data": [
{ "id": 1, "name": "Математика", "created_at": "2026-08-25 10:00:00" },
{ "id": 2, "name": "Физика", "created_at": "2026-08-25 10:00:00" }
]
}

###admin.tagCreate
Создание нового тега (имя должно быть уникальным).

Метод: POST
URL: /api.php?method=admin.tagCreate
Тело (JSON):
{
"name": "Химия"
}

Ответ (200):
{
"success": true,
"data": {
"tag_id": 3,
"message": "Тег создан"
}
}

###admin.tagUpdate
Обновление названия тега (уникальность имени проверяется).

Метод: POST
URL: /api.php?method=admin.tagUpdate
Тело (JSON):
{
"id": 1,
"name": "Алгебра"
}

Ответ (200):
{
"success": true,
"data": {
"message": "Тег обновлён"
}
}

###admin.tagDelete
Удаление тега (если не используется в мероприятиях).

Метод: POST
URL: /api.php?method=admin.tagDelete
Тело (JSON):
{
"id": 3
}

Ответ (200):
{
"success": true,
"data": {
"message": "Тег удалён"
}
}

##Управление шаблонами документов (только для роли admin)

###admin.template.list – список всех шаблонов документов.

Метод: GET
URL: /api.php?method=admin.template.list
Ответ (200):
{
"success": true,
"data": [
{
"id": 1,
"name": "Согласие на обработку данных",
"description": "Общее согласие для учеников",
"content": "Я, {PARENT_FIO}, даю согласие...",
"signature_level": "simple",
"requires_file": false,
"created_at": "2026-08-25 10:00:00",
"updated_at": "2026-08-25 10:00:00"
}
]
}

###admin.template.create – создание нового шаблона.

Метод: POST
URL: /api.php?method=admin.template.create
Тело (JSON):
{
"name": "Согласие на обработку данных",
"description": "Общее согласие для учеников",
"content": "Я, {PARENT_FIO}, даю согласие на обработку персональных данных моего ребенка {STUDENT_FIO}, класс {CLASS}, для целей образовательного процесса.",
"signature_level": "simple", // возможные значения: simple, sms, goskey
"requires_file": false // true – требуется загрузка файла, false – только подпись
}
Ответ (200):
{
"success": true,
"data": {
"template_id": 1,
"message": "Шаблон создан"
}
}
Ошибки: 400 – отсутствуют обязательные поля name или content; 403 – недостаточно прав.

###admin.template.update – обновление существующего шаблона.

Метод: POST
URL: /api.php?method=admin.template.update
Тело (JSON):
{
"id": 1,
"name": "Новое название",
"content": "Новый текст с плейсхолдерами",
"signature_level": "sms",
"requires_file": true
}
Ответ (200):
{
"success": true,
"data": {
"message": "Шаблон обновлён"
}
}
Ошибки: 400 – id не указан или нет полей для обновления; 404 – шаблон не найден.

###admin.template.delete – удаление шаблона.

Метод: POST
URL: /api.php?method=admin.template.delete
Тело (JSON):
{
"id": 1
}
Ответ (200):
{
"success": true,
"data": {
"message": "Шаблон удалён"
}
}
Ошибки: 400 – id не указан; 404 – шаблон не найден.

##Работа с документами (родитель и ученик)

###parent.uploadDocument – загрузка документа родителем для своего ребёнка.

Метод: POST (multipart/form-data)
URL: /api.php?method=parent.uploadDocument
Параметры формы:

student_id (int, обязательное) – ID ученика

template_id (int, опционально) – ID шаблона, если документ по шаблону

event_id (int, опционально) – ID мероприятия, если документ привязан к мероприятию

file (файл, опционально) – загружаемый файл (если требуется)

signature (boolean, опционально) – true для простой подписи (галочка), используется если шаблон не требует файла

expiry_date (string, опционально) – срок действия в формате YYYY-MM-DD

Ответ (200):
{
"success": true,
"data": {
"document_id": 10,
"status": "pending",
"message": "Документ отправлен на проверку"
}
}
Ошибки: 400 – не указан student_id или не загружен файл при необходимости; 403 – ученик не принадлежит родителю; 404 – шаблон не найден.

###parent.getDocuments – список документов для своих детей.

Метод: GET
URL: /api.php?method=parent.getDocuments
Параметры URL (опционально):

student_id – ID конкретного ребёнка (если не указан – все дети)

status – фильтр по статусу (pending, approved, rejected, expired)

Ответ (200):
{
"success": true,
"data": [
{
"id": 10,
"student_id": 1,
"template_name": "Согласие на обработку данных",
"status": "pending",
"expiry_date": "2027-01-01",
"created_at": "2026-08-25 12:00:00",
"file_url": "/api.php?method=document.download&id=10" // если есть файл
}
]
}

###student.uploadDocument – загрузка документа учеником для себя.

Метод: POST (multipart/form-data)
URL: /api.php?method=student.uploadDocument
Параметры формы:

template_id (int, опционально) – ID шаблона

event_id (int, опционально) – ID мероприятия

file (файл, опционально) – загружаемый файл

signature (boolean, опционально) – true для простой подписи

expiry_date (string, опционально) – YYYY-MM-DD

Ответ (200): аналогичен parent.uploadDocument.

###student.getDocuments – список своих документов.

Метод: GET
URL: /api.php?method=student.getDocuments
Параметры URL (опционально):

status – фильтр по статусу

Ответ (200): аналогичен parent.getDocuments, но только для текущего ученика.

##Модерация документов (роли admin, moderator, teacher)

###moderator.getPendingDocuments – получение списка документов на проверке.

Метод: GET
URL: /api.php?method=moderator.getPendingDocuments
Параметры URL (опционально):

student_id – фильтр по ученику

event_id – фильтр по мероприятию

Ответ (200):
{
"success": true,
"data": [
{
"id": 10,
"student_name": "Иванов Иван",
"class_name": "10А",
"template_name": "Согласие на обработку данных",
"uploaded_by_name": "Петрова Мария",
"status": "pending",
"created_at": "2026-08-25 12:00:00",
"file_url": "/api.php?method=document.download&id=10"
}
]
}
Примечание: для учителя возвращаются только документы учеников его класса.

###moderator.confirmDocument – подтверждение документа.

Метод: POST
URL: /api.php?method=moderator.confirmDocument
Тело (JSON):
{
"document_id": 10,
"comment": "Все верно" // опционально
}
Ответ (200):
{
"success": true,
"data": {
"document_id": 10,
"status": "approved"
}
}
Ошибки: 400 – document_id не указан; 404 – документ не найден; 409 – документ не в статусе pending.

###moderator.rejectDocument – отклонение документа с указанием причины.

Метод: POST
URL: /api.php?method=moderator.rejectDocument
Тело (JSON):
{
"document_id": 10,
"comment": "Недостаточно подтверждающих документов" // обязательно
}
Ответ (200):
{
"success": true,
"data": {
"document_id": 10,
"status": "rejected"
}
}
Ошибки: 400 – отсутствует comment; 404 – документ не найден; 409 – документ не в статусе pending.

##Управление согласиями (родитель)

###parent.giveConsent – дать согласие (общее, на мероприятие, на обработку данных).

Метод: POST
URL: /api.php?method=parent.giveConsent
Тело (JSON):
{
"student_id": 1,
"type": "general", // general, event, data_processing
"version": "1.0" // версия документа согласия
}
Ответ (200):
{
"success": true,
"data": {
"consent_id": 5,
"status": "active"
}
}
Ошибки: 400 – не указаны student_id, type или version; 403 – ученик не принадлежит родителю; 409 – уже есть активное согласие этого типа (деактивируется автоматически).

###parent.revokeConsent – отзыв согласия.

Метод: POST
URL: /api.php?method=parent.revokeConsent
Тело (JSON):
{
"consent_id": 5
}
Ответ (200):
{
"success": true,
"data": {
"message": "Согласие отозвано"
}
}
Ошибки: 400 – consent_id не указан; 404 – согласие не найдено; 403 – доступ запрещён (не своё согласие); 409 – уже отозвано.

###parent.getConsents – список согласий с фильтрацией.

Метод: GET
URL: /api.php?method=parent.getConsents
Параметры URL (опционально):

student_id – ID ребёнка

type – тип согласия (general, event, data_processing)

Ответ (200):
{
"success": true,
"data": [
{
"id": 5,
"user_id": 2,
"student_id": 1,
"type": "general",
"version": "1.0",
"status": "active",
"given_at": "2026-08-25 12:30:00",
"revoked_at": null,
"ip_address": "192.168.1.1"
}
]
}

##Системный метод для проверки сроков действия

###system.checkExpiredDocuments – принудительная проверка и обновление статусов истекших документов. Вызывается по крону или администратором вручную.

Метод: POST
URL: /api.php?method=system.checkExpiredDocuments
Требует роли admin.

Ответ (200):
{
"success": true,
"data": {
"expired_count": 3,
"message": "Обновлено 3 документов со статусом expired"
}
}

##Классный руководитель (методы, требующие роль teacher или admin)
###teacher.seating.get – получить рассадку класса.

Параметры: class_id (опционально, для учителя по умолчанию его класс).

Ответ: массив объектов с полями student_id, student_name, table_number, seat_number.

###teacher.seating.set – установить/обновить рассадку класса.

Параметры: class_id (опционально), seats – массив [{student_id, table_number, seat_number}].

Ответ: {message: "Рассадка обновлена"}.

###teacher.seating.clear – очистить рассадку класса.

Параметры: class_id (опционально).

Ответ: {message: "Рассадка очищена"}.

###teacher.attendance.mark – отметить присутствие учеников на обеде.

Параметры: date (опционально, по умолчанию сегодня), student_ids (массив ID) или class_id (отметить весь класс).

Ответ: {marked_count: N, date: "YYYY-MM-DD"}.

###teacher.attendance.get – получить отметки за период.

Параметры: class_id (опционально), date_from, date_to.

Ответ: массив записей с полями student_id, student_name, date, is_present.

##Сотрудник столовой (роль canteen или admin)
###canteen.seating.getToday – получить итоговую рассадку на сегодня с учётом отметок.

Параметры: date (опционально, по умолчанию сегодня).

Ответ: структура {first_flow: [...], second_flow: [...]}, где каждый элемент содержит table_number, seat_number, student_id, student_name, class_name, is_dormitory.

###canteen.seating.export – выгрузить рассадку в CSV или JSON.

Параметры: date (опционально), format (csv или json, по умолчанию csv).

Для CSV: возвращает файл с заголовками.

Для JSON: возвращает массив данных.

###canteen.stats.get – статистика по питанию.

Параметры: date (опционально).

Ответ: {date, present_count, total_students, special_meals: [...]}.

##Администратор
###admin.canteen.special.add – добавить особый график питания.

Параметры: student_id, description.

Ответ: {special_id, message}.

###admin.canteen.special.remove – удалить особый график.

Параметры: id.

Ответ: {message}.

###admin.canteen.special.list – список особых графиков (доступно также для canteen).

Параметры: student_id (опционально).

Ответ: массив записей.

##Примечания по использованию

Для загрузки файлов документов используйте multipart/form-data (как и для достижений).

Допустимые расширения: pdf, jpg, jpeg, png, doc, docx, odt. Максимальный размер – 10 МБ.

Все действия логируются в storage/logs/documents.log.

Для документа, созданного по шаблону, можно использовать автоподстановку плейсхолдеров: {STUDENT_FIO}, {PARENT_FIO}, {CLASS}, {DATE}. Эти данные подставляются при генерации содержимого (пока не реализовано в API, но модель DocumentTemplate содержит метод renderContent, который можно использовать при необходимости).

Срок действия (expiry_date) – опциональный атрибут; при его наступлении документ автоматически переводится в статус expired.

##Коды ошибок (общие)

400 – Неверный запрос (не хватает полей, неверный формат и т.п.)
401 – Не авторизован (отсутствует или невалидный JWT)
403 – Доступ запрещён (недостаточно прав)
404 – Ресурс не найден
409 – Конфликт (уже существует, уже подтверждён и т.п.)
429 – Слишком много запросов (Rate Limiting)
500 – Внутренняя ошибка сервера

###Ошибки, связанные с мероприятиями:

403 – мероприятие недоступно для ученика (не подходит по классу или типу проживания)
409 – попытка повторной записи на мероприятие или превышение лимита участников; попытка обновить уже начавшееся мероприятие
404 – мероприятие, категория или тег не найдены

##Примечания

Все временные пароли генерируются родителем при создании ученика и передаются ученику.

Ученик может просматривать только свои достижения; модератор/администратор – все.

Файлы достижений хранятся вне публичной директории и доступны только через защищённый метод achievement.download.

Логирование всех действий учеников и модераторов ведётся в storage/logs/portfolio.log.
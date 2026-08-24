### `parent.getChildren`
Получить список детей текущего родителя (требуется роль `parent`).
- **Метод:** GET
- **Заголовок:** `Authorization: Bearer <token>`
- **Ответ:** массив учеников с полями id, user_id, snils_hash, total_points, class_id, full_name, email, class_name.

### `parent.addChild`
Привязать ученика к родителю (требуется роль `parent`).
- **Метод:** POST (JSON)
- **Заголовок:** `Authorization: Bearer <token>`
- **Параметры:** `student_id` (int)
- **Ответ:** `{"success":true,"data":null,"error":null}`
- **Ошибки:** 400 (нет student_id), 404 (ученик не найден), 409 (уже связан).
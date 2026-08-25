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

## auth.register
Регистрация нового родителя.

**Метод:** `POST /api.php?method=auth.register`  
**Параметры (JSON):**
- `full_name` (string, обязательное)
- `email` (string, обязательное, валидный email)
- `password` (string, обязательное, мин. 6 символов)
- `consent` (boolean, обязательное, должно быть `true`)

**Успешный ответ (200):**
```json
{
  "success": true,
  "data": {
    "token": "jwt_token",
    "user": {
      "id": 1,
      "email": "parent@example.com",
      "full_name": "Иван Иванов",
      "role": "parent"
    }
  }
}
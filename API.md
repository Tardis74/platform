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

## Аутентификация

### auth.login
- **POST** – вход
- **Параметры:** `email`, `password`
- **Ответ:** `{ token, user }`

### auth.register
- **POST** – регистрация родителя
- **Параметры:** `full_name`, `email`, `password`, `consent`
- **Ответ:** `{ token, user }`

### auth.refresh
- **POST** – обновление токена
- **Ответ:** `{ token }`

---

## Родительский функционал

### parent.getChildren
- **GET** – список детей родителя
- **Ответ:** массив детей с полями `id, full_name, class_name, status, is_dormitory`

### parent.addChild
- **POST** – добавить ребёнка (создать нового или отправить заявку)
- **Параметры:** `snils`, `full_name`, `class_id` (опц), `birth_date` (опц), `is_dormitory` (опц)
- **Ответ:** `{ student_id, temporary_password, status, message }`

### parent.linkChild
- **POST** – привязать существующего ученика по СНИЛС
- **Параметры:** `snils`
- **Ответ:** `{ student_id, status, message }`

---

## Классный руководитель

### teacher.getPendingStudents
- **Роль:** `teacher`
- **GET** – список учеников своего класса, ожидающих подтверждения
- **Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "full_name": "Иванов Иван",
      "birth_date": "2010-05-10",
      "snils_masked": "123***45",
      "class_name": "10А",
      "created_at": "2026-08-25 10:00:00"
    }
  ]
}
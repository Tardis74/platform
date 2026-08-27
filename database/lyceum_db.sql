-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Авг 27 2026 г., 18:49
-- Версия сервера: 10.4.27-MariaDB
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `lyceum_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Название (например, 2024-2025)',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 – текущий учебный год',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `academic_years`
--

INSERT INTO `academic_years` (`id`, `name`, `start_date`, `end_date`, `is_current`, `created_at`, `updated_at`) VALUES
(1, '123', '0026-09-01', '2027-08-31', 1, '2026-08-27 09:42:22', '2026-08-27 10:25:16');

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `moderator_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `achievement_categories`
--

CREATE TABLE `achievement_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `weight` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `achievement_categories`
--

INSERT INTO `achievement_categories` (`id`, `name`, `weight`, `created_at`) VALUES
(1, 'Олимпиада', 10, '2026-08-25 14:16:21'),
(2, 'Спорт', 5, '2026-08-25 14:16:21'),
(3, 'Творчество', 3, '2026-08-25 14:16:21');

-- --------------------------------------------------------

--
-- Структура таблицы `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (year(`created_at`) * 100 + month(`created_at`))
(
PARTITION p202401 VALUES LESS THAN (202402) ENGINE=InnoDB,
PARTITION p202402 VALUES LESS THAN (202403) ENGINE=InnoDB,
PARTITION p202403 VALUES LESS THAN (202404) ENGINE=InnoDB,
PARTITION p202404 VALUES LESS THAN (202405) ENGINE=InnoDB,
PARTITION p202405 VALUES LESS THAN (202406) ENGINE=InnoDB,
PARTITION p202406 VALUES LESS THAN (202407) ENGINE=InnoDB,
PARTITION p202407 VALUES LESS THAN (202408) ENGINE=InnoDB,
PARTITION p202408 VALUES LESS THAN (202409) ENGINE=InnoDB,
PARTITION p202409 VALUES LESS THAN (202410) ENGINE=InnoDB,
PARTITION p202410 VALUES LESS THAN (202411) ENGINE=InnoDB,
PARTITION p202411 VALUES LESS THAN (202412) ENGINE=InnoDB,
PARTITION p202412 VALUES LESS THAN (202501) ENGINE=InnoDB,
PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE=InnoDB
);

-- --------------------------------------------------------

--
-- Структура таблицы `canteen_attendance`
--

CREATE TABLE `canteen_attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `is_present` tinyint(1) NOT NULL DEFAULT 0,
  `marked_by` int(10) UNSIGNED NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `canteen_seating`
--

CREATE TABLE `canteen_seating` (
  `id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `table_number` int(10) UNSIGNED NOT NULL,
  `seat_number` int(10) UNSIGNED NOT NULL,
  `updated_by` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `canteen_special_meals`
--

CREATE TABLE `canteen_special_meals` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `description` text NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `classes`
--

CREATE TABLE `classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `academic_year_id` int(10) UNSIGNED DEFAULT NULL,
  `year` year(4) NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `classes`
--

INSERT INTO `classes` (`id`, `name`, `academic_year_id`, `year`, `teacher_id`, `is_archived`) VALUES
(2, '8Б', 1, 0000, NULL, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `consents`
--

CREATE TABLE `consents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `type` enum('general','event','data_processing') NOT NULL,
  `version` varchar(20) NOT NULL,
  `status` enum('active','revoked') NOT NULL DEFAULT 'active',
  `given_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `documents`
--

CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED DEFAULT NULL,
  `event_id` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `signature_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`signature_data`)),
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `moderator_comment` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `document_templates`
--

CREATE TABLE `document_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text NOT NULL,
  `signature_level` enum('simple','sms','goskey') NOT NULL DEFAULT 'simple',
  `requires_file` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `max_participants` int(10) UNSIGNED DEFAULT NULL,
  `current_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `points` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `requires_confirmation` tinyint(1) NOT NULL DEFAULT 1,
  `requires_documents` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','cancelled','completed') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `event_categories`
--

CREATE TABLE `event_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `event_categories`
--

INSERT INTO `event_categories` (`id`, `name`, `created_at`) VALUES
(2, '123', '2026-08-27 10:01:43');

-- --------------------------------------------------------

--
-- Структура таблицы `event_class_access`
--

CREATE TABLE `event_class_access` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `event_dormitory_access`
--

CREATE TABLE `event_dormitory_access` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `is_dormitory` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `comment` text DEFAULT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `event_tags`
--

CREATE TABLE `event_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `event_tags`
--

INSERT INTO `event_tags` (`id`, `name`, `created_at`) VALUES
(1, '213', '2026-08-27 09:43:04');

-- --------------------------------------------------------

--
-- Структура таблицы `event_tag_links`
--

CREATE TABLE `event_tag_links` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `kpp_logs`
--

CREATE TABLE `kpp_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (year(`created_at`) * 100 + month(`created_at`))
(
PARTITION p202401 VALUES LESS THAN (202402) ENGINE=InnoDB,
PARTITION p202402 VALUES LESS THAN (202403) ENGINE=InnoDB,
PARTITION p202403 VALUES LESS THAN (202404) ENGINE=InnoDB,
PARTITION p202404 VALUES LESS THAN (202405) ENGINE=InnoDB,
PARTITION p202405 VALUES LESS THAN (202406) ENGINE=InnoDB,
PARTITION p202406 VALUES LESS THAN (202407) ENGINE=InnoDB,
PARTITION p202407 VALUES LESS THAN (202408) ENGINE=InnoDB,
PARTITION p202408 VALUES LESS THAN (202409) ENGINE=InnoDB,
PARTITION p202409 VALUES LESS THAN (202410) ENGINE=InnoDB,
PARTITION p202410 VALUES LESS THAN (202411) ENGINE=InnoDB,
PARTITION p202411 VALUES LESS THAN (202412) ENGINE=InnoDB,
PARTITION p202412 VALUES LESS THAN (202501) ENGINE=InnoDB,
PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE=InnoDB
);

-- --------------------------------------------------------

--
-- Структура таблицы `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `entry_time` datetime DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','exited','returned','overdue') NOT NULL DEFAULT 'pending',
  `qr_code` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `moderator_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `link_requests`
--

CREATE TABLE `link_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `parents`
--

CREATE TABLE `parents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `parents`
--

INSERT INTO `parents` (`id`, `user_id`, `phone`) VALUES
(4, 5, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `parent_student`
--

CREATE TABLE `parent_student` (
  `parent_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  `label` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `group_name`, `label`) VALUES
(1, 'users.view', 'Пользователи', 'users.view'),
(2, 'users.create', 'Пользователи', 'users.create'),
(3, 'users.edit', 'Пользователи', 'users.edit'),
(4, 'users.delete', 'Пользователи', 'users.delete'),
(5, 'users.block', 'Пользователи', 'users.block'),
(6, 'classes.view', 'Классы', 'classes.view'),
(7, 'classes.create', 'Классы', 'classes.create'),
(8, 'classes.edit', 'Классы', 'classes.edit'),
(9, 'classes.archive', 'Классы', 'classes.archive'),
(10, 'tags.view', 'Теги', 'tags.view'),
(11, 'tags.create', 'Теги', 'tags.create'),
(12, 'tags.edit', 'Теги', 'tags.edit'),
(13, 'tags.delete', 'Теги', 'tags.delete'),
(14, 'templates.view', 'Шаблоны', 'templates.view'),
(15, 'templates.create', 'Шаблоны', 'templates.create'),
(16, 'templates.edit', 'Шаблоны', 'templates.edit'),
(17, 'templates.delete', 'Шаблоны', 'templates.delete'),
(18, 'categories.view', 'Категории', 'categories.view'),
(19, 'categories.create', 'Категории', 'categories.create'),
(20, 'categories.edit', 'Категории', 'categories.edit'),
(21, 'categories.delete', 'Категории', 'categories.delete'),
(22, 'reports.view', 'Отчёты', 'reports.view'),
(23, 'reports.generate', 'Отчёты', 'reports.generate'),
(24, 'rating.view', 'Рейтинг', 'rating.view'),
(25, 'rating.build', 'Рейтинг', 'rating.build'),
(26, 'rating.publish', 'Рейтинг', 'rating.publish'),
(27, 'permissions.view', 'Права доступа', 'permissions.view'),
(28, 'permissions.edit', 'Права доступа', 'permissions.edit'),
(29, 'audit.view', 'Аудит', 'audit.view'),
(30, 'events.view', 'Мероприятия', 'events.view'),
(31, 'events.create', 'Мероприятия', 'events.create'),
(32, 'events.edit', 'Мероприятия', 'events.edit'),
(33, 'events.delete', 'Мероприятия', 'events.delete'),
(34, 'documents.view', 'Документы', 'documents.view'),
(35, 'documents.moderate', 'Документы', 'documents.moderate'),
(36, 'achievements.view', 'Достижения', 'achievements.view'),
(37, 'achievements.moderate', 'Достижения', 'achievements.moderate'),
(38, 'leave.view', 'Заявления на выход', 'leave.view'),
(39, 'leave.approve', 'Заявления на выход', 'leave.approve'),
(40, 'canteen.view', 'Питание', 'canteen.view'),
(41, 'canteen.edit', 'Питание', 'canteen.edit'),
(42, 'kpp.view', 'КПП', 'kpp.view'),
(43, 'kpp.scan', 'КПП', 'kpp.scan'),
(44, 'dashboard.view', 'Дашборд', 'dashboard.view');

-- --------------------------------------------------------

--
-- Структура таблицы `queue`
--

CREATE TABLE `queue` (
  `id` int(10) UNSIGNED NOT NULL,
  `job` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `available_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `rate_limits`
--

CREATE TABLE `rate_limits` (
  `ip` varchar(45) NOT NULL,
  `window_start` int(10) UNSIGNED NOT NULL,
  `count` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `rate_limits`
--

INSERT INTO `rate_limits` (`ip`, `window_start`, `count`) VALUES
('127.0.0.1', 1787839134, 2),
('127.0.0.1', 1787839147, 3),
('127.0.0.1', 1787839157, 3),
('127.0.0.1', 1787839167, 3),
('127.0.0.1', 1787839168, 1),
('127.0.0.1', 1787839172, 1),
('127.0.0.1', 1787839173, 1),
('127.0.0.1', 1787839178, 2),
('127.0.0.1', 1787839190, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `ratings`
--

CREATE TABLE `ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `period` varchar(7) NOT NULL COMMENT 'Месяц в формате YYYY-MM',
  `class_ids` text DEFAULT NULL COMMENT 'JSON-массив ID классов',
  `category_ids` text DEFAULT NULL COMMENT 'JSON-массив ID категорий достижений',
  `published` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 – опубликован',
  `data` longtext NOT NULL COMMENT 'JSON с данными рейтинга (место, идентификатор, баллы, комментарий)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `report_jobs`
--

CREATE TABLE `report_jobs` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'Тип отчёта (events, city, portfolio и т.д.)',
  `params` longtext NOT NULL COMMENT 'Параметры в JSON',
  `status` enum('pending','processing','ready','error') NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL COMMENT 'Путь к сгенерированному файлу',
  `error_message` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`key`, `value`, `updated_at`) VALUES
('rating_show_place', '1', '2026-08-27 08:56:04');

-- --------------------------------------------------------

--
-- Структура таблицы `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `snils_hash` varchar(64) NOT NULL,
  `class_id` int(10) UNSIGNED DEFAULT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `birth_date` date DEFAULT NULL,
  `is_dormitory` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('awaiting_confirmation','active','rejected') NOT NULL DEFAULT 'awaiting_confirmation',
  `rejection_reason` text DEFAULT NULL,
  `snils_masked` varchar(14) DEFAULT NULL COMMENT 'маскированный СНИЛС для отображения (первые 3 и последние 2)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','teacher','parent','student','canteen','moderator','educator','kpp','custom') NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `full_name`, `created_at`, `updated_at`, `first_login`, `deleted_at`) VALUES
(1, 'admin@example.com', '$2y$10$lQxdksaPab0v5LqkWfrRk.Fu1rl70qoRrrwyir7I0/ytwJREP3K5C', 'admin', 'Admin User', '2026-08-24 22:34:14', NULL, 1, NULL),
(5, 'svelegzanin3@yandex.ru', '$2y$10$vu2D2Fx.DVywyXOu9LKfReNnIYTi57gtRW8fAowZKWfz7lqYMUZBC', 'parent', 'Велегжанин Сергей Олегович', '2026-08-25 21:18:25', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `permission` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `status` (`status`);

--
-- Индексы таблицы `achievement_categories`
--
ALTER TABLE `achievement_categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`,`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Индексы таблицы `canteen_attendance`
--
ALTER TABLE `canteen_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily` (`student_id`,`date`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `date` (`date`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Индексы таблицы `canteen_seating`
--
ALTER TABLE `canteen_seating`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seating` (`class_id`,`student_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `fk_seating_user` (`updated_by`);

--
-- Индексы таблицы `canteen_special_meals`
--
ALTER TABLE `canteen_special_meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `fk_special_user` (`created_by`);

--
-- Индексы таблицы `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `fk_classes_academic_year` (`academic_year_id`);

--
-- Индексы таблицы `consents`
--
ALTER TABLE `consents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`);

--
-- Индексы таблицы `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `status` (`status`),
  ADD KEY `expiry_date` (`expiry_date`),
  ADD KEY `fk_document_uploader` (`uploaded_by`);

--
-- Индексы таблицы `document_templates`
--
ALTER TABLE `document_templates`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `start_datetime` (`start_datetime`),
  ADD KEY `status` (`status`),
  ADD KEY `fk_events_creator` (`created_by`);

--
-- Индексы таблицы `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `event_class_access`
--
ALTER TABLE `event_class_access`
  ADD PRIMARY KEY (`event_id`,`class_id`),
  ADD KEY `fk_event_class_class` (`class_id`);

--
-- Индексы таблицы `event_dormitory_access`
--
ALTER TABLE `event_dormitory_access`
  ADD PRIMARY KEY (`event_id`,`is_dormitory`);

--
-- Индексы таблицы `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`student_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`);

--
-- Индексы таблицы `event_tags`
--
ALTER TABLE `event_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `event_tag_links`
--
ALTER TABLE `event_tag_links`
  ADD PRIMARY KEY (`event_id`,`tag_id`),
  ADD KEY `fk_event_tag_tag` (`tag_id`);

--
-- Индексы таблицы `kpp_logs`
--
ALTER TABLE `kpp_logs`
  ADD PRIMARY KEY (`id`,`created_at`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Индексы таблицы `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`),
  ADD KEY `start_time` (`start_time`),
  ADD KEY `end_time` (`end_time`),
  ADD KEY `fk_leave_parent` (`parent_id`),
  ADD KEY `fk_leave_creator` (`created_by`);

--
-- Индексы таблицы `link_requests`
--
ALTER TABLE `link_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Индексы таблицы `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_id` (`user_id`);

--
-- Индексы таблицы `parent_student`
--
ALTER TABLE `parent_student`
  ADD PRIMARY KEY (`parent_id`,`student_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Индексы таблицы `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_available_at` (`available_at`);

--
-- Индексы таблицы `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`ip`,`window_start`),
  ADD KEY `idx_window_start` (`window_start`);

--
-- Индексы таблицы `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `period` (`period`),
  ADD KEY `published` (`published`);

--
-- Индексы таблицы `report_jobs`
--
ALTER TABLE `report_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_by` (`created_by`);

--
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Индексы таблицы `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `snils_hash` (`snils_hash`),
  ADD UNIQUE KEY `uk_user_id` (`user_id`),
  ADD KEY `idx_snils_hash` (`snils_hash`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_class_id_status` (`class_id`,`status`),
  ADD KEY `idx_status` (`status`);

--
-- Индексы таблицы `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Индексы таблицы `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `achievement_categories`
--
ALTER TABLE `achievement_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `canteen_attendance`
--
ALTER TABLE `canteen_attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `canteen_seating`
--
ALTER TABLE `canteen_seating`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `canteen_special_meals`
--
ALTER TABLE `canteen_special_meals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `consents`
--
ALTER TABLE `consents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `document_templates`
--
ALTER TABLE `document_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `event_tags`
--
ALTER TABLE `event_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `kpp_logs`
--
ALTER TABLE `kpp_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `link_requests`
--
ALTER TABLE `link_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT для таблицы `queue`
--
ALTER TABLE `queue`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `report_jobs`
--
ALTER TABLE `report_jobs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `fk_achievements_category` FOREIGN KEY (`category_id`) REFERENCES `achievement_categories` (`id`),
  ADD CONSTRAINT `fk_achievements_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `canteen_attendance`
--
ALTER TABLE `canteen_attendance`
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_user` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `canteen_seating`
--
ALTER TABLE `canteen_seating`
  ADD CONSTRAINT `fk_seating_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seating_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seating_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `canteen_special_meals`
--
ALTER TABLE `canteen_special_meals`
  ADD CONSTRAINT `fk_special_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_special_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_classes_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_classes_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `consents`
--
ALTER TABLE `consents`
  ADD CONSTRAINT `fk_consent_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_consent_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_document_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_document_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_document_template` FOREIGN KEY (`template_id`) REFERENCES `document_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_document_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_category` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_events_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `event_class_access`
--
ALTER TABLE `event_class_access`
  ADD CONSTRAINT `fk_event_class_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_class_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `event_dormitory_access`
--
ALTER TABLE `event_dormitory_access`
  ADD CONSTRAINT `fk_event_dormitory` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `fk_registration_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_registration_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `event_tag_links`
--
ALTER TABLE `event_tag_links`
  ADD CONSTRAINT `fk_event_tag_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_tag_tag` FOREIGN KEY (`tag_id`) REFERENCES `event_tags` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_leave_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leave_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `link_requests`
--
ALTER TABLE `link_requests`
  ADD CONSTRAINT `fk_link_requests_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_link_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `fk_parents_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `parent_student`
--
ALTER TABLE `parent_student`
  ADD CONSTRAINT `fk_parent_student_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_parent_student_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `report_jobs`
--
ALTER TABLE `report_jobs`
  ADD CONSTRAINT `fk_report_jobs_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_students_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teachers_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `fk_user_perm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Создание базы данных (если не существует)
CREATE DATABASE IF NOT EXISTS `lyceum_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lyceum_db`;

-- Таблица пользователей
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'teacher', 'parent', 'student') NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица студентов (расширение пользователей, роль 'student')
CREATE TABLE `students` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `snils_hash` VARCHAR(64) NOT NULL UNIQUE,
    `class_id` INT UNSIGNED NULL,
    `total_points` INT NOT NULL DEFAULT 0,
    `birth_date` DATE NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    INDEX `idx_snils_hash` (`snils_hash`),
    INDEX `idx_class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица родителей (пользователи с ролью 'parent')
CREATE TABLE `parents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `phone` VARCHAR(20) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Связь родитель-студент (многие ко многим)
CREATE TABLE `parent_student` (
    `parent_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`parent_id`, `student_id`),
    INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица учителей (пользователи с ролью 'teacher')
CREATE TABLE `teachers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `subject` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица классов
CREATE TABLE `classes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `year` YEAR NOT NULL,
    `teacher_id` INT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица мероприятий
CREATE TABLE `events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `event_date` DATETIME NOT NULL,
    `max_participants` INT UNSIGNED NULL,
    `current_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'open', 'closed', 'cancelled') NOT NULL DEFAULT 'draft',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_event_date` (`event_date`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Регистрация на мероприятия (ученики)
CREATE TABLE `event_registrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_event_student` (`event_id`, `student_id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Аудиторный лог (партиционирование по месяцам)
-- Внешний ключ опущен из-за ограничений MySQL 5.7 для партиционированных таблиц
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `old_value` JSON NULL,
    `new_value` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`, `created_at`),
    INDEX `idx_user_id` (`user_id`)   -- добавлен индекс для быстрых запросов
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    PARTITION p202403 VALUES LESS THAN (202404),
    PARTITION p202404 VALUES LESS THAN (202405),
    PARTITION p202405 VALUES LESS THAN (202406),
    PARTITION p202406 VALUES LESS THAN (202407),
    PARTITION p202407 VALUES LESS THAN (202408),
    PARTITION p202408 VALUES LESS THAN (202409),
    PARTITION p202409 VALUES LESS THAN (202410),
    PARTITION p202410 VALUES LESS THAN (202411),
    PARTITION p202411 VALUES LESS THAN (202412),
    PARTITION p202412 VALUES LESS THAN (202501),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Лог КПП (партиционирование по месяцам)
-- Внешний ключ опущен по тем же причинам
CREATE TABLE `kpp_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `direction` ENUM('in', 'out') NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`, `created_at`),
    INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    PARTITION p202403 VALUES LESS THAN (202404),
    PARTITION p202404 VALUES LESS THAN (202405),
    PARTITION p202405 VALUES LESS THAN (202406),
    PARTITION p202406 VALUES LESS THAN (202407),
    PARTITION p202407 VALUES LESS THAN (202408),
    PARTITION p202408 VALUES LESS THAN (202409),
    PARTITION p202409 VALUES LESS THAN (202410),
    PARTITION p202410 VALUES LESS THAN (202411),
    PARTITION p202411 VALUES LESS THAN (202412),
    PARTITION p202412 VALUES LESS THAN (202501),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Очередь фоновых задач
CREATE TABLE `queue` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job` VARCHAR(255) NOT NULL,
    `payload` JSON NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `available_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_available_at` (`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting для API
CREATE TABLE `rate_limits` (
    `ip` VARCHAR(45) NOT NULL,
    `window_start` INT UNSIGNED NOT NULL,
    `count` INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`ip`, `window_start`),
    INDEX `idx_window_start` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Внешние ключи (кроме партиционированных таблиц) =====
ALTER TABLE `students` ADD CONSTRAINT `fk_students_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
ALTER TABLE `students` ADD CONSTRAINT `fk_students_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL;
ALTER TABLE `parents` ADD CONSTRAINT `fk_parents_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
ALTER TABLE `parent_student` ADD CONSTRAINT `fk_parent_student_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE CASCADE;
ALTER TABLE `parent_student` ADD CONSTRAINT `fk_parent_student_student_id` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE;
ALTER TABLE `teachers` ADD CONSTRAINT `fk_teachers_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
ALTER TABLE `classes` ADD CONSTRAINT `fk_classes_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL;
ALTER TABLE `events` ADD CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE;
ALTER TABLE `event_registrations` ADD CONSTRAINT `fk_event_registrations_event_id` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE;
ALTER TABLE `event_registrations` ADD CONSTRAINT `fk_event_registrations_student_id` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE;
-- Для audit_logs и kpp_logs внешние ключи не добавляем (ограничение MySQL 5.7)
-- Вместо этого созданы индексы для производительности.
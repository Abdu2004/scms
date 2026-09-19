-- ============================================================
-- Online Student Complaint and Resolution Tracking System
-- Database Schema
-- Engine: MySQL / MariaDB (WAMP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS complaint_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE complaint_system;

-- ------------------------------------------------------------
-- 1. DEPARTMENTS
-- Complaints get routed/assigned to these (Academic, Hostel,
-- Security, Library, Bursary, ICT, Student Affairs, etc.)
-- ------------------------------------------------------------
CREATE TABLE departments (
    department_id   INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    description     VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. COMPLAINT CATEGORIES
-- Academics, Administrative, Hostel, Security, Library, Welfare
-- ------------------------------------------------------------
CREATE TABLE complaint_categories (
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    description     VARCHAR(255) DEFAULT NULL,
    -- categories can have a default department they route to
    default_department_id INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (default_department_id) REFERENCES departments(department_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. USERS
-- Single table for both students and admins, split by role.
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    role            ENUM('student', 'admin', 'super_admin') NOT NULL DEFAULT 'student',
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) DEFAULT NULL,

    -- Student-specific fields (NULL for admins)
    reg_number      VARCHAR(50) DEFAULT NULL UNIQUE,
    faculty         VARCHAR(100) DEFAULT NULL,
    level           VARCHAR(20) DEFAULT NULL,

    -- Admin-specific field: which department they manage (NULL = super_admin)
    department_id   INT DEFAULT NULL,

    status          ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON DELETE SET NULL,

    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. COMPLAINTS
-- Core record. Every complaint is filed by a student,
-- belongs to a category, gets routed to a department,
-- and can be assigned to a specific admin.
-- ------------------------------------------------------------
CREATE TABLE complaints (
    complaint_id    INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code   VARCHAR(20) NOT NULL UNIQUE,  -- e.g. CMP-2026-000123, shown to student

    student_id      INT NOT NULL,
    category_id     INT NOT NULL,
    department_id   INT DEFAULT NULL,             -- filled once routed/reassigned
    assigned_admin_id INT DEFAULT NULL,            -- specific admin handling it

    title           VARCHAR(200) NOT NULL,
    description     TEXT NOT NULL,
    priority        ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status          ENUM('pending', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'pending',

    resolution_note TEXT DEFAULT NULL,             -- admin's final note on resolution

    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at     TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES complaint_categories(category_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_admin_id) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_status (status),
    INDEX idx_student (student_id),
    INDEX idx_department (department_id),
    INDEX idx_tracking_code (tracking_code)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. COMPLAINT ATTACHMENTS (optional supporting evidence)
-- ------------------------------------------------------------
CREATE TABLE complaint_attachments (
    attachment_id   INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id    INT NOT NULL,
    file_path       VARCHAR(255) NOT NULL,
    original_name   VARCHAR(255) NOT NULL,
    uploaded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. COMPLAINT STATUS HISTORY
-- Full audit trail — satisfies the "tracking" objective.
-- Every status change (including initial submission) logged here.
-- ------------------------------------------------------------
CREATE TABLE complaint_status_history (
    history_id      INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id    INT NOT NULL,
    old_status      ENUM('pending', 'in_progress', 'resolved', 'rejected') DEFAULT NULL,
    new_status      ENUM('pending', 'in_progress', 'resolved', 'rejected') NOT NULL,
    changed_by      INT DEFAULT NULL,   -- user_id of admin/student who triggered it
    remarks         VARCHAR(500) DEFAULT NULL,
    changed_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL,

    INDEX idx_complaint (complaint_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. NOTIFICATIONS
-- In-app + email notification log for status updates.
-- ------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,          -- recipient
    complaint_id    INT DEFAULT NULL,
    message         VARCHAR(500) NOT NULL,
    channel         ENUM('in_app', 'email') NOT NULL DEFAULT 'in_app',
    is_read         BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,

    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SEED DATA (starter departments & categories)
-- ------------------------------------------------------------
INSERT INTO departments (name, description) VALUES
('Academic Affairs', 'Handles course, exam, and result related complaints'),
('Student Affairs', 'General student welfare and conduct matters'),
('Hostel/Housing', 'Accommodation and hostel-related issues'),
('Security', 'Campus security and safety concerns'),
('Library', 'Library services and resources'),
('Bursary', 'Fee payment and financial matters'),
('ICT Center', 'Portal, network, and system-related issues');

INSERT INTO complaint_categories (name, description, default_department_id) VALUES
('Academics', 'Course registration, exams, results', 1),
('Administrative', 'General admin/office related issues', 2),
('Hostel', 'Accommodation issues', 3),
('Security', 'Safety and security concerns', 4),
('Library', 'Library services', 5),
('Welfare', 'Student welfare matters', 2);

-- ------------------------------------------------------------
-- SEED DATA (default super admin — change password after first login)
-- password_hash below is a placeholder; generate with password_hash() in PHP
-- ------------------------------------------------------------
-- Default super admin credentials:
--   Email:    admin@complaintsystem.local
--   Password: admin123
-- (Change the password immediately after first login.)
INSERT INTO users (role, full_name, email, password_hash, status) VALUES
('super_admin', 'System Administrator', 'admin@complaintsystem.local', '$2y$10$50hMLGm4pv7C7yR14jYbTeAEPCbXyiby0xJRROmmHeu/CBPXt8Bo.', 'active');

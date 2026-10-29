-- ADDITIVE migration. Back up the database before use. Existing tables are not dropped.
-- Execute schema additions with scripts/migrate.php, which verifies whether columns exist.
CREATE TABLE IF NOT EXISTS ite_attendance_scans (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 event_id INT NOT NULL,
 student_id INT NOT NULL,
 session_date DATE NOT NULL,
 slot ENUM('morning_in','morning_out','afternoon_in','afternoon_out') NOT NULL,
 scanned_at DATETIME NOT NULL,
 recorded_by INT NOT NULL,
 UNIQUE KEY uq_event_student_day_slot(event_id, student_id, session_date, slot),
 KEY idx_student_date(student_id, session_date),
 KEY idx_event_date(event_id, session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ite_event_fines (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 event_id INT NOT NULL,
 student_id INT NOT NULL,
 amount DECIMAL(9,2) NOT NULL DEFAULT 20.00,
 reason VARCHAR(160) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_event_student(event_id, student_id),
 KEY idx_student(student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ite_fine_payments (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 fine_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(9,2) NOT NULL,
 reference VARCHAR(120) NOT NULL,
 recorded_by INT NOT NULL,
 recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_fine(fine_id),
 UNIQUE KEY uq_fine_ref(fine_id,reference),
 CONSTRAINT fk_ite_payment_fine FOREIGN KEY (fine_id) REFERENCES ite_event_fines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ite_clearance_approvals (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 student_id INT NOT NULL,
 school_year VARCHAR(30) NOT NULL,
 semester VARCHAR(30) NOT NULL,
 approved_by INT NOT NULL,
 approved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_student_period(student_id, school_year, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ite_staff_profiles (
 user_id INT NOT NULL PRIMARY KEY,
 firstname VARCHAR(100) NOT NULL DEFAULT '',
 lastname VARCHAR(100) NOT NULL DEFAULT '',
 middle_initial VARCHAR(10) NOT NULL DEFAULT '',
 birthdate DATE NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ite_audit_logs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 actor_user_id INT NOT NULL,
 action VARCHAR(80) NOT NULL,
 target VARCHAR(180) NOT NULL,
 details TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_actor(actor_user_id),
 KEY idx_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

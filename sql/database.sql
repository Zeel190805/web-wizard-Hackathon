-- Student Portal Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS student_portal;
USE student_portal;

-- Users table with enhanced features
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    remember_token VARCHAR(255) NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255) NULL,
    reset_token VARCHAR(255) NULL,
    reset_expires DATETIME NULL,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    theme_preference ENUM('light', 'dark') DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Student profiles with comprehensive information
CREATE TABLE student_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    course VARCHAR(100),
    department VARCHAR(100),
    year_of_study INT,
    semester VARCHAR(20),
    gpa DECIMAL(3,2),
    profile_picture VARCHAR(255),
    bio TEXT,
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_name (first_name, last_name),
    INDEX idx_course (course),
    INDEX idx_department (department),
    INDEX idx_year (year_of_study)
);

-- Activity logs for tracking user actions
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Academic records for student performance tracking
CREATE TABLE academic_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    grade VARCHAR(5),
    credits INT,
    instructor VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student_profiles(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_semester_year (semester, year)
);

-- Documents for file management
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    document_type ENUM('transcript', 'certificate', 'id_proof', 'other') NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student_profiles(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_document_type (document_type)
);

-- Notifications system
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
);

-- System settings
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (email, password_hash, role, status, email_verified) VALUES 
('admin@portal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', TRUE);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'Student Portal', 'Website name'),
('maintenance_mode', '0', 'Enable/disable maintenance mode'),
('registration_enabled', '1', 'Enable/disable user registration'),
('max_file_upload_size', '5242880', 'Maximum file upload size in bytes'),
('password_min_length', '8', 'Minimum password length'),
('session_timeout', '1800', 'Session timeout in seconds');

-- Insert sample student data
INSERT INTO users (email, password_hash, role, status, email_verified) VALUES 
('john.doe@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', TRUE),
('jane.smith@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', TRUE),
('mike.wilson@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', TRUE);

-- Get the user IDs for sample students
SET @john_id = (SELECT id FROM users WHERE email = 'john.doe@student.com');
SET @jane_id = (SELECT id FROM users WHERE email = 'jane.smith@student.com');
SET @mike_id = (SELECT id FROM users WHERE email = 'mike.wilson@student.com');

-- Insert sample student profiles
INSERT INTO student_profiles (user_id, student_id, first_name, last_name, phone, course, department, year_of_study, gpa) VALUES
(@john_id, 'STU001', 'John', 'Doe', '+1234567890', 'Computer Science', 'Engineering', 3, 3.8),
(@jane_id, 'STU002', 'Jane', 'Smith', '+1234567891', 'Business Administration', 'Management', 2, 3.9),
(@mike_id, 'STU003', 'Mike', 'Wilson', '+1234567892', 'Mechanical Engineering', 'Engineering', 4, 3.7);

-- Create views for easier data access
CREATE VIEW student_summary AS
SELECT 
    u.id,
    u.email,
    u.status,
    u.last_login,
    sp.student_id,
    sp.first_name,
    sp.last_name,
    sp.phone,
    sp.course,
    sp.department,
    sp.year_of_study,
    sp.gpa,
    sp.profile_picture
FROM users u
JOIN student_profiles sp ON u.id = sp.user_id
WHERE u.role = 'student';

-- Create stored procedure for user statistics
DELIMITER //
CREATE PROCEDURE GetUserStatistics()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student') AS total_students,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') AS total_admins,
        (SELECT COUNT(*) FROM users WHERE status = 'active') AS active_users,
        (SELECT COUNT(*) FROM users WHERE status = 'inactive') AS inactive_users,
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) AS new_registrations_today,
        (SELECT AVG(gpa) FROM student_profiles WHERE gpa IS NOT NULL) AS average_gpa;
END //
DELIMITER ;

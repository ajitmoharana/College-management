-- ============================================
-- COLLEGE MANAGEMENT SYSTEM - FULL SQL (NEW)
-- ============================================

CREATE DATABASE IF NOT EXISTS college_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE college_management;

-- RESET: drop tables safely
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS faculty;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS notices;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','student','faculty') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- DEPARTMENTS
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  head_name VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- STUDENTS
CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  student_id VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  dob DATE,
  gender ENUM('Male','Female','Other'),
  address TEXT,
  department_id INT NULL,
  semester INT NOT NULL DEFAULT 1,
  admission_year YEAR,
  status ENUM('Active','Inactive','Graduated') NOT NULL DEFAULT 'Active',
  photo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_students_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  INDEX idx_students_dept (department_id),
  INDEX idx_students_status (status)
) ENGINE=InnoDB;

-- FACULTY
CREATE TABLE faculty (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  faculty_id VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  department_id INT NULL,
  designation VARCHAR(100),
  qualification VARCHAR(150),
  experience INT NOT NULL DEFAULT 0,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_faculty_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_faculty_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  INDEX idx_faculty_dept (department_id),
  INDEX idx_faculty_status (status)
) ENGINE=InnoDB;

-- COURSES
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(20) NOT NULL UNIQUE,
  course_name VARCHAR(150) NOT NULL,
  department_id INT NULL,
  faculty_id INT NULL,
  semester INT,
  credits INT NOT NULL DEFAULT 3,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_courses_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_courses_faculty FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE SET NULL,
  INDEX idx_courses_dept (department_id),
  INDEX idx_courses_faculty (faculty_id)
) ENGINE=InnoDB;

-- ENROLLMENTS
CREATE TABLE enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_enrollment (student_id, course_id),
  CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_enroll_course  FOREIGN KEY (course_id) REFERENCES courses(id)  ON DELETE CASCADE,
  INDEX idx_enroll_student (student_id),
  INDEX idx_enroll_course (course_id)
) ENGINE=InnoDB;

-- ATTENDANCE
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  date DATE NOT NULL,
  status ENUM('Present','Absent','Late') NOT NULL DEFAULT 'Present',
  marked_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_attendance (student_id, course_id, date),
  CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_course  FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE,
  CONSTRAINT fk_att_marker  FOREIGN KEY (marked_by)  REFERENCES users(id)    ON DELETE SET NULL,
  INDEX idx_att_course_date (course_id, date),
  INDEX idx_att_student (student_id)
) ENGINE=InnoDB;

-- GRADES
CREATE TABLE grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  exam_type ENUM('Internal','Mid-Term','Final','Assignment') NOT NULL DEFAULT 'Final',
  marks_obtained DECIMAL(5,2) NOT NULL DEFAULT 0,
  total_marks DECIMAL(5,2) NOT NULL DEFAULT 100,
  grade VARCHAR(5),
  remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_grades_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_grades_course  FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE,
  INDEX idx_grades_student (student_id),
  INDEX idx_grades_course (course_id)
) ENGINE=InnoDB;

-- NOTICES
CREATE TABLE notices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  posted_by INT NULL,
  target_role ENUM('all','student','faculty') NOT NULL DEFAULT 'all',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notice_user FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_notice_target (target_role),
  INDEX idx_notice_date (created_at)
) ENGINE=InnoDB;

-- ============================================
-- DEMO DATA (password for all users = "password")
-- ============================================

INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@college.edu',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. John Smith', 'john.smith@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty'),
('Prof. Maria Garcia', 'maria.garcia@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty'),
('Alice Brown', 'alice@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Bob Wilson', 'bob@college.edu',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO departments (name, code, head_name) VALUES
('Computer Science', 'CS', 'Dr. James Wilson'),
('Electronics Engineering', 'EE', 'Dr. Sarah Johnson'),
('Mechanical Engineering', 'ME', 'Prof. Robert Davis'),
('Business Administration', 'BA', 'Dr. Emily Clark');

INSERT INTO faculty (user_id, faculty_id, name, email, department_id, designation, qualification, experience) VALUES
(2, 'FAC0001', 'Dr. John Smith', 'john.smith@college.edu', 1, 'Professor', 'PhD Computer Science', 10),
(3, 'FAC0002', 'Prof. Maria Garcia', 'maria.garcia@college.edu', 1, 'Associate Professor', 'MPhil Mathematics', 7);

INSERT INTO students (user_id, student_id, name, email, department_id, semester, admission_year) VALUES
(4, 'STU0001', 'Alice Brown', 'alice@college.edu', 1, 3, 2023),
(5, 'STU0002', 'Bob Wilson',  'bob@college.edu',  1, 3, 2023);

INSERT INTO courses (course_code, course_name, department_id, faculty_id, semester, credits) VALUES
('CS101', 'Introduction to Programming', 1, 1, 1, 4),
('CS201', 'Data Structures',            1, 1, 3, 4),
('CS301', 'Database Management',        1, 2, 5, 3),
('CS401', 'Web Development',            1, 2, 5, 3);

-- Enroll sample students into courses (important so student/faculty pages show data)
INSERT INTO enrollments (student_id, course_id) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2);

INSERT INTO notices (title, content, posted_by, target_role) VALUES
('Welcome to the New Semester',
 'Welcome back students and faculty! Classes begin on Monday. Please check your schedule.',
 1, 'all');
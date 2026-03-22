# 🎓 College Management System

A College Management System built with **HTML, CSS, JavaScript, PHP, and MySQL**.

---

## 📁 Project Structure

```text
college-management/
│
├── index.php
├── login.php
├── database.sql
├── README.md
├── .gitignore
│
├── css/
│   └── style.css
│
├── js/
│   └── main.js
│
├── includes/
│   ├── auth.php
│   └── db.php
│
├── admin/
│   ├── sidebar.php
│   ├── dashboard.php
│   ├── students.php
│   ├── faculty.php
│   ├── courses.php
│   ├── departments.php
│   ├── attendance.php
│   ├── grades.php
│   ├── notices.php
│   └── logout.php
│
├── faculty/
│   ├── attendance.php
│   ├── dashboard.php
│   ├── grades.php
│   ├── my_courses.php
│   ├── notices.php
│   ├── profile.php
│   ├── students.php
│   └── logout.php
│
└── student/
    ├── attendance.php
    ├── courses.php
    ├── dashboard.php
    ├── grades.php
    ├── notices.php
    ├── profile.php
    └── logout.php
````

---

## ⚙️ Setup Instructions

### 1. Requirements

* PHP 7.4 or above
* MySQL 5.7 or above
* Apache Server (XAMPP / WAMP / LAMP)
* PHP `mysqli` extension enabled

### 2. Database Setup

Import the `database.sql` file into MySQL using phpMyAdmin or MySQL CLI:

```sql
SOURCE /path/to/college-management/database.sql;
```

### 3. Configure Database

Edit the `includes/db.php` file if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_management');
```

### 4. Place Project Folder

Copy the `college-management` folder to:

* **XAMPP:** `C:/xampp/htdocs/`
* **WAMP:** `C:/wamp64/www/`
* **Linux:** `/var/www/html/`

### 5. Run the Project

Open your browser and visit:

```text
http://localhost/college-management/
```

---

## 🔐 Demo Login Credentials

These accounts are for **local demo/testing only**.

| Role    | Name              | Email                                                                 | Password |
| ------- | ----------------- | --------------------------------------------------------------------- | -------- |
| Admin   | AJ                | [adminaj@gmail.com](mailto:adminaj@gmail.com)                         | password |
| Faculty | Ajit Moharana     | [ajitmoharana515@gmail.com](mailto:ajitmoharana515@gmail.com)         | password |
| Faculty | Jyotirmayee Das   | [jyotirmayeedas.2005@gmail.com](mailto:jyotirmayeedas.2005@gmail.com) | password |
| Student | Pratyush Tripathy | [pratyushtripathy@gmail.com](mailto:pratyushtripathy@gmail.com)       | password |
| Student | Abhishek Agarwal  | [abhishekagarwal@gmail.com](mailto:abhishekagarwal@gmail.com)         | password |

> The default password hash in `database.sql` is for the string `password`.

---

## 🧩 Features

### Admin Panel

* Dashboard overview
* Manage students
* Manage faculty
* Manage courses
* Manage departments
* Mark and view attendance
* Manage grades
* Post notices
* Logout

### Faculty Panel

* Dashboard overview
* View assigned courses
* Mark attendance
* Enter grades
* View students
* View notices
* View profile
* Logout

### Student Panel

* Dashboard overview
* View courses
* View attendance
* View grades
* View notices
* View profile
* Logout

---

## 🗄️ Database Modules

The system includes the following database tables:

* `users`
* `departments`
* `students`
* `faculty`
* `courses`
* `enrollments`
* `attendance`
* `grades`
* `notices`

---

## 🎨 Tech Stack

| Layer    | Technology              |
| -------- | ----------------------- |
| Frontend | HTML5, CSS3, JavaScript |
| Backend  | PHP                     |
| Database | MySQL                   |
| Styling  | Custom CSS              |

---

## 📌 Default Academic Data

### Departments

* Computer Science
* Electronics Engineering
* Mechanical Engineering
* Business Administration

### Faculty

* Ajit Moharana
* Jyotirmayee Das

### Students

* Pratyush Tripathy
* Abhishek Agarwal

### Courses

* CS101 — Introduction to Programming
* CS201 — Data Structures
* CS301 — Database Management
* CS401 — Web Development

---

## 🔒 Security Notes

This project is intended for **learning and local development**.

Before using it in production, you should review and improve:

* database credentials handling
* password policy
* HTTPS / secure cookie settings
* server configuration
* logging and error handling
* backup and recovery
* input validation and auditing

---

## 🔧 Future Improvements

* Add edit/update functionality
* Add password change module
* Add timetable management
* Add fee management
* Add export to PDF/Excel
* Add profile photo upload
* Add email notification system

---

## 📄 License

Free to use for educational purposes.

```
```

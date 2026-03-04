# 🎓 College Management System

A complete, production-ready College Management System built with **HTML, CSS, JavaScript, PHP & MySQL**.

---

## 📁 Project Structure

```
college-management/
│
├── index.php                  ← Auto-redirects based on login
├── login.php                  ← Login page (all roles)
├── database.sql               ← Full database setup
│
├── css/
│   └── style.css              ← All styles (dark theme)
│
├── js/
│   └── main.js                ← JavaScript utilities
│
├── includes/
│   ├── db.php                 ← Database connection
│   └── auth.php               ← Session / auth helpers
│
├── admin/
│   ├── sidebar.php            ← Admin sidebar include
│   ├── dashboard.php          ← Admin dashboard with stats
│   ├── students.php           ← Add / list / delete students
│   ├── faculty.php            ← Add / list / delete faculty
│   ├── courses.php            ← Add / list / delete courses
│   ├── departments.php        ← Add / list / delete departments
│   ├── attendance.php         ← Mark & view attendance
│   ├── grades.php             ← Add / view grades
│   ├── notices.php            ← Post / delete notices
│   └── logout.php
│
├── student/
│   ├── dashboard.php          ← Student home (stats + courses + grades)
│   └── logout.php
│
└── faculty/
    ├── dashboard.php          ← Faculty home (courses + students)
    └── logout.php
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP 7.4+
- MySQL 5.7+
- Apache (XAMPP / WAMP / LAMP)

### 2. Database Setup
```sql
-- In phpMyAdmin or MySQL CLI:
SOURCE /path/to/college-management/database.sql;
```

### 3. Configure Database
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Your MySQL username
define('DB_PASS', '');          // Your MySQL password
define('DB_NAME', 'college_management');
```

### 4. Place Project
Copy the `college-management/` folder to:
- XAMPP: `C:/xampp/htdocs/`
- WAMP:  `C:/wamp64/www/`
- Linux: `/var/www/html/`

### 5. Open in Browser
```
http://localhost/college-management/
```

---

## 🔐 Demo Login Credentials

| Role    | Email                    | Password |
|---------|--------------------------|----------|
| Admin   | admin@college.edu        | password |
| Faculty | john.smith@college.edu   | password |
| Student | alice@college.edu        | password |

> Note: The default password hash in database.sql is for the string `password`.
> After setup, change passwords via phpMyAdmin or add a Change Password page.

---

## 🧩 Features

### Admin Panel
- ✅ Dashboard with live statistics
- ✅ Student Management (Add, View, Delete, Status Toggle)
- ✅ Faculty Management (Add, View, Delete)
- ✅ Course Management (Add, View, Delete, Assign Faculty)
- ✅ Department Management (with counts)
- ✅ Attendance Marking (by course + date, bulk mark)
- ✅ Grade Entry (with auto grade calculation A+/A/B/C/D/F)
- ✅ Notice Board (post to all / students / faculty)

### Student Portal
- ✅ Personal dashboard with stats
- ✅ Enrolled courses view
- ✅ Attendance percentage
- ✅ Grade records
- ✅ Notice board

### Faculty Portal
- ✅ Dashboard with course stats
- ✅ View assigned courses
- ✅ Quick access to attendance & grades

---

## 🎨 Tech Stack

| Layer    | Technology         |
|----------|--------------------|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend  | PHP 7.4+ (Procedural + MySQLi) |
| Database | MySQL 5.7+         |
| Fonts    | Google Fonts (Syne + DM Sans) |
| Design   | Custom dark theme with CSS variables |

---

## 🔧 To Extend

- Add **Timetable** module
- Add **Fee Management** module
- Add **Edit** functionality for students/faculty
- Add **Export to PDF/Excel** for reports
- Add **Profile Photo Upload**
- Add **Email Notifications**
- Add pagination for large datasets

---

## 📄 License

Free to use for educational purposes.

# Assignment Uploader - Full Stack Web Application

A comprehensive web-based platform for seamless assignment submission, management, and evaluation. Built with modern technologies following effective web design principles.

## 🎬 Demo Video

https://github.com/user-attachments/assets/24fe02d8-2797-48ab-9915-d59f6509893f

## 📋 Project Overview

**Assignment Hub** is a full-stack assignment management system that provides:
- **For Students**: Easy assignment submission in multiple formats (PDF, Images, PPT, Word, etc.)
- **For Faculty**: Efficient assignment management and grading system
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices
- **Modern Architecture**: REST API backend with React-based dashboard

## 🏗️ Technology Stack

### Frontend
- **HTML5** - Semantic markup and structure
- **CSS3** - Modern styling with Flexbox, Grid, and animations
- **JavaScript & jQuery** - DOM manipulation and form validation
- **React 18** - Interactive dashboard components
- **Responsive Design** - Mobile-first approach

### Backend
- **PHP 7+** - Server-side logic and API endpoints
- **MySQL** - Relational database management
- **JWT** - Token-based authentication
- **RESTful API** - Standard HTTP methods

## 📁 Project Structure

```
assignment-uploader/
├── frontend/
│   ├── index.html              # Landing page
│   ├── login.html              # Login page
│   ├── register.html           # Registration page
│   ├── dashboard.html          # Main dashboard
│   ├── css/
│   │   ├── style.css           # Main stylesheet
│   │   └── responsive.css      # Mobile responsive styles
│   ├── js/
│   │   ├── jquery-3.6.0.min.js # jQuery library
│   │   ├── main.js             # Global navigation logic
│   │   ├── login.js            # Login validation
│   │   ├── register.js         # Registration validation
│   │   └── dashboard.js        # Dashboard functionality
│   ├── components/
│   │   └── Dashboard.jsx       # React components
│   └── assets/                 # Images, icons, etc.
│
├── backend/
│   ├── config/
│   │   └── database.php        # Database connection & schema
│   ├── api/
│   │   ├── auth.php            # Authentication endpoints
│   │   ├── dashboard.php       # Dashboard statistics
│   │   ├── assignments.php     # Assignment management
│   │   ├── submissions.php     # Submission handling
│   │   ├── grades.php          # Grading system
│   │   └── upload.php          # File upload handling
│   └── uploads/                # Uploaded files (auto-created)
│
└── README.md                   # This file
```

## 🚀 Getting Started

### Prerequisites
- **XAMPP** or **WAMP** (Apache + PHP + MySQL)
- **PHP 7.0+**
- **MySQL 5.7+**
- **Modern Web Browser** (Chrome, Firefox, Safari, Edge)

### Installation Steps

#### 1. **Setup Database**

1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. The database will be created automatically when you first access the application
3. Or manually create: `CREATE DATABASE assignment_uploader;`

#### 2. **Configure Database Connection**

Edit `backend/config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Add your MySQL password
define('DB_NAME', 'assignment_uploader');
```

#### 3. **Place Project Files**

- Copy the entire `assignment-uploader` folder to:
  - **XAMPP**: `C:\xampp\htdocs\`
  - **WAMP**: `C:\wamp64\www\`

#### 4. **Start Services**

- Start Apache and MySQL from XAMPP/WAMP Control Panel

#### 5. **Access Application**

- **Landing Page**: `http://localhost/assignment-uploader/frontend/index.html`
- **Register**: Create new account (Student or Faculty)
- **Login**: Use registered credentials

### Test Accounts (After Registration)

You can create test accounts with:
- **Student Account**: Email as student identifier, role as "Student"
- **Faculty Account**: Email as faculty identifier, role as "Faculty"

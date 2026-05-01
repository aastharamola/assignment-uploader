<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'assignment_uploader');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

if (!$conn->select_db(DB_NAME)) {
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if ($conn->query($sql) === TRUE) {
        $conn->select_db(DB_NAME);
    }
}

initializeDatabase($conn);

function initializeDatabase($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        firstName VARCHAR(100) NOT NULL,
        lastName VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        rollNumber VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('student', 'faculty') NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating users table: " . $conn->error);
    }

    $sql = "CREATE TABLE IF NOT EXISTS assignments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description LONGTEXT,
        subject VARCHAR(100) NOT NULL,
        dueDate DATETIME NOT NULL,
        fileName VARCHAR(255) DEFAULT NULL,
        filePath VARCHAR(255) DEFAULT NULL,
        createdBy INT NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (createdBy) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating assignments table: " . $conn->error);
    }

    $sql = "CREATE TABLE IF NOT EXISTS submissions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        assignmentId INT NOT NULL,
        studentId INT NOT NULL,
        fileName VARCHAR(255) NOT NULL,
        filePath VARCHAR(255) NOT NULL,
        plagiarismScore DECIMAL(5,2) DEFAULT NULL,
        plagiarismStatus ENUM('clean', 'suspected', 'pending') DEFAULT 'pending',
        matchedSubmissionId INT DEFAULT NULL,
        submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('submitted', 'graded') DEFAULT 'submitted',
        FOREIGN KEY (assignmentId) REFERENCES assignments(id),
        FOREIGN KEY (studentId) REFERENCES users(id),
        UNIQUE KEY unique_submission (assignmentId, studentId)
    )";
    
    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating submissions table: " . $conn->error);
    }

    $sql = "CREATE TABLE IF NOT EXISTS grades (
        id INT PRIMARY KEY AUTO_INCREMENT,
        submissionId INT NOT NULL,
        score INT NOT NULL,
        maxScore INT DEFAULT 100,
        grade CHAR(1),
        feedback LONGTEXT,
        gradedBy INT NOT NULL,
        gradedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (submissionId) REFERENCES submissions(id),
        FOREIGN KEY (gradedBy) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating grades table: " . $conn->error);
    }

    ensureSubmissionPlagiarismColumns($conn);
    ensureAssignmentFileColumns($conn);
}

function ensureAssignmentFileColumns($conn) {
    ensureColumnExists($conn, 'assignments', 'fileName', "ALTER TABLE assignments ADD COLUMN fileName VARCHAR(255) DEFAULT NULL AFTER dueDate");
    ensureColumnExists($conn, 'assignments', 'filePath', "ALTER TABLE assignments ADD COLUMN filePath VARCHAR(255) DEFAULT NULL AFTER fileName");
}

function ensureSubmissionPlagiarismColumns($conn) {
    ensureColumnExists($conn, 'submissions', 'plagiarismScore', "ALTER TABLE submissions ADD COLUMN plagiarismScore DECIMAL(5,2) DEFAULT NULL AFTER filePath");
    ensureColumnExists($conn, 'submissions', 'plagiarismStatus', "ALTER TABLE submissions ADD COLUMN plagiarismStatus ENUM('clean', 'suspected', 'pending') DEFAULT 'pending' AFTER plagiarismScore");
    ensureColumnExists($conn, 'submissions', 'matchedSubmissionId', "ALTER TABLE submissions ADD COLUMN matchedSubmissionId INT DEFAULT NULL AFTER plagiarismStatus");
}

function ensureColumnExists($conn, $table, $column, $alterSql) {
    $tableName = $conn->real_escape_string($table);
    $columnName = $conn->real_escape_string($column);
    $checkSql = "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'";
    $result = $conn->query($checkSql);

    if ($result && $result->num_rows === 0) {
        if ($conn->query($alterSql) !== TRUE) {
            error_log("Error adding {$column} to {$table}: " . $conn->error);
        }
    }
}

function getUserFromToken() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    $payloadEncoded = strtr($parts[1], '-_', '+/');
    $payloadEncoded = str_pad($payloadEncoded, strlen($payloadEncoded) + (4 - strlen($payloadEncoded) % 4) % 4, '=');
    $payload = json_decode(base64_decode($payloadEncoded), true);

    if (!$payload || !isset($payload['userId'])) {
        return null;
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }

    return $payload['userId'];
}

function sanitize($input) {
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>

<?php

require_once '../config/database.php';
require_once '../utils/Mailer.php';

$userId = getUserFromToken();

if (!$userId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    getAssignments($conn, $userId, $role);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    createAssignment($conn, $userId);
}

function getAssignments($conn, $userId, $role) {
    if ($role === 'faculty') {
        $stmt = $conn->prepare("SELECT id, title, description, subject, dueDate as deadline, fileName, filePath, createdBy FROM assignments WHERE createdBy = ? ORDER BY dueDate DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql = "SELECT id, title, description, subject, dueDate as deadline, fileName, filePath, createdBy FROM assignments ORDER BY dueDate DESC";
        $result = $conn->query($sql);
    }

    if ($result && $result->num_rows > 0) {
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        echo json_encode([
            'success' => true,
            'data' => $assignments
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
    }
}

function createAssignment($conn, $userId) {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $dueDate = sanitize($_POST['dueDate'] ?? '');

    $fileName = null;
    $filePath = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/assignments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath = $_FILES['file']['tmp_name'];
        $originalFileName = $_FILES['file']['name'];
        $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        
        $newFileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $originalFileName);
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $fileName = $originalFileName;
            $filePath = 'backend/uploads/assignments/' . $newFileName;
        }
    }

    if (empty($title) || empty($dueDate)) {
        echo json_encode([
            'success' => false,
            'message' => 'Title and due date are required'
        ]);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO assignments (title, description, subject, dueDate, fileName, filePath, createdBy) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $title, $description, $subject, $dueDate, $fileName, $filePath, $userId);

    if ($stmt->execute()) {
        $newAssignmentId = $stmt->insert_id;

        
        $students = $conn->query(
            "SELECT email, firstName, lastName FROM users WHERE role = 'student'"
        );
        if ($students && $students->num_rows > 0) {
            while ($student = $students->fetch_assoc()) {
                Mailer::sendNewAssignmentNotification(
                    $student['email'],
                    $student['firstName'] . ' ' . $student['lastName'],
                    $title,
                    $subject,
                    $dueDate,
                    $description
                );
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Assignment created successfully',
            'id'      => $newAssignmentId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create assignment: ' . $stmt->error
        ]);
    }

    $stmt->close();
}
?>

<?php

require_once '../config/database.php';

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
    getSubmissions($conn, $userId, $role);
}

function getSubmissions($conn, $userId, $role) {
    if ($role === 'student') {
        
        $stmt = $conn->prepare("SELECT s.id, a.title as assignmentTitle, s.fileName, s.submittedAt, s.status, s.plagiarismScore, s.plagiarismStatus, s.matchedSubmissionId
                               FROM submissions s 
                               JOIN assignments a ON s.assignmentId = a.id 
                               WHERE s.studentId = ? 
                               ORDER BY s.submittedAt DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        
        $stmt = $conn->prepare("SELECT s.id, a.title as assignmentTitle, s.fileName, s.submittedAt, s.status, s.plagiarismScore, s.plagiarismStatus, s.matchedSubmissionId, u.firstName, u.lastName 
                               FROM submissions s 
                               JOIN assignments a ON s.assignmentId = a.id 
                               JOIN users u ON s.studentId = u.id
                               WHERE a.createdBy = ? 
                               ORDER BY s.submittedAt DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if ($result && $result->num_rows > 0) {
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $row['submittedDate'] = $row['submittedAt'];
            unset($row['submittedAt']);
            $submissions[] = $row;
        }
        echo json_encode([
            'success' => true,
            'data' => $submissions
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
    }
}
?>

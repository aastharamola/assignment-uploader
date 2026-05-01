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

$data = [];

if ($role === 'student') {
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM assignments");
    $stmt->execute();
    $data['totalAssignments'] = $stmt->get_result()->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM submissions WHERE studentId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data['submitted'] = $stmt->get_result()->fetch_assoc()['count'];

    $data['pending'] = $data['totalAssignments'] - $data['submitted'];

    $stmt = $conn->prepare("SELECT AVG(g.score) as avg FROM grades g 
                           JOIN submissions s ON g.submissionId = s.id 
                           WHERE s.studentId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $data['avgGrade'] = $result['avg'] ?? 0;

} else if ($role === 'faculty') {
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM assignments WHERE createdBy = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data['totalAssignments'] = $stmt->get_result()->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM submissions s 
                           JOIN assignments a ON s.assignmentId = a.id 
                           WHERE a.createdBy = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data['totalSubmissions'] = $stmt->get_result()->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM submissions s 
                           JOIN assignments a ON s.assignmentId = a.id 
                           WHERE a.createdBy = ? AND s.status = 'submitted'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data['pendingGrading'] = $stmt->get_result()->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM submissions s 
                           JOIN assignments a ON s.assignmentId = a.id 
                           WHERE a.createdBy = ? AND s.status = 'graded'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data['graded'] = $stmt->get_result()->fetch_assoc()['count'];
}

echo json_encode([
    'success' => true,
    'data' => $data
]);
?>

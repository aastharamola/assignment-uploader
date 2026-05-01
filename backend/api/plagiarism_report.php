<?php
require_once '../config/database.php';

$userId = getUserFromToken();

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$submissionId = $_GET['id'] ?? null;

if (!$submissionId) {
    echo json_encode(['success' => false, 'message' => 'Submission ID is required']);
    exit();
}


$sql = "SELECT 
            s1.id as subId, s1.plagiarismScore, s1.plagiarismStatus, s1.fileName as currentFile,
            u1.firstName as s1First, u1.lastName as s1Last, u1.rollNumber as s1Roll,
            s2.id as matchedSubId, s2.fileName as matchedFile,
            u2.firstName as s2First, u2.lastName as s2Last, u2.rollNumber as s2Roll,
            a.title as assignmentTitle
        FROM submissions s1
        JOIN users u1 ON s1.studentId = u1.id
        JOIN assignments a ON s1.assignmentId = a.id
        LEFT JOIN submissions s2 ON s1.matchedSubmissionId = s2.id
        LEFT JOIN users u2 ON s2.studentId = u2.id
        WHERE s1.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if ($report) {
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Report not found'
    ]);
}
?>


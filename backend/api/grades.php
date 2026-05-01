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
    getGrades($conn, $userId, $role);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    submitGrade($conn, $userId, $role);
}

function getGrades($conn, $userId, $role) {
    if ($role === 'student') {
        
        $stmt = $conn->prepare("SELECT g.id, a.title as assignmentTitle, g.score, g.maxScore, g.grade, g.feedback, g.gradedAt as gradedDate 
                               FROM grades g 
                               JOIN submissions s ON g.submissionId = s.id 
                               JOIN assignments a ON s.assignmentId = a.id 
                               WHERE s.studentId = ? 
                               ORDER BY g.gradedAt DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        
        $stmt = $conn->prepare("SELECT g.id, a.title as assignmentTitle, g.score, g.maxScore, g.grade, g.feedback, g.gradedAt as gradedDate, u.firstName, u.lastName 
                               FROM grades g 
                               JOIN submissions s ON g.submissionId = s.id 
                               JOIN assignments a ON s.assignmentId = a.id 
                               JOIN users u ON s.studentId = u.id
                               WHERE g.gradedBy = ? 
                               ORDER BY g.gradedAt DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if ($result && $result->num_rows > 0) {
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        echo json_encode([
            'success' => true,
            'data' => $grades
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
    }
}

function submitGrade($conn, $userId, $role) {
    if ($role !== 'faculty') {
        echo json_encode([
            'success' => false,
            'message' => 'Only faculty can grade assignments'
        ]);
        return;
    }

    $submissionId = sanitize($_POST['submissionId'] ?? '');
    $score = sanitize($_POST['score'] ?? '');
    $maxScore = sanitize($_POST['maxScore'] ?? '100');
    $feedback = sanitize($_POST['feedback'] ?? '');

    if (empty($submissionId) || empty($score)) {
        echo json_encode([
            'success' => false,
            'message' => 'Submission ID and score are required'
        ]);
        return;
    }

    
    $percentage = ($score / $maxScore) * 100;
    if ($percentage >= 90) {
        $grade = 'A';
    } elseif ($percentage >= 80) {
        $grade = 'B';
    } elseif ($percentage >= 70) {
        $grade = 'C';
    } elseif ($percentage >= 60) {
        $grade = 'D';
    } else {
        $grade = 'F';
    }

    
    $stmt = $conn->prepare("SELECT id FROM grades WHERE submissionId = ?");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $existing = $stmt->get_result()->num_rows > 0;

    if ($existing) {
        
        $stmt = $conn->prepare("UPDATE grades SET score = ?, maxScore = ?, grade = ?, feedback = ?, gradedBy = ?, gradedAt = NOW() WHERE submissionId = ?");
        $stmt->bind_param("iissii", $score, $maxScore, $grade, $feedback, $userId, $submissionId);
    } else {
        
        $stmt = $conn->prepare("INSERT INTO grades (submissionId, score, maxScore, grade, feedback, gradedBy) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssi", $submissionId, $score, $maxScore, $grade, $feedback, $userId);
    }

    if ($stmt->execute()) {
        
        $stmt2 = $conn->prepare("UPDATE submissions SET status = 'graded' WHERE id = ?");
        $stmt2->bind_param("i", $submissionId);
        $stmt2->execute();

        
        $infoStmt = $conn->prepare(
            "SELECT u.email, u.firstName, u.lastName, a.title
             FROM submissions s
             JOIN users u ON s.studentId = u.id
             JOIN assignments a ON s.assignmentId = a.id
             WHERE s.id = ?"
        );
        $infoStmt->bind_param("i", $submissionId);
        $infoStmt->execute();
        $info = $infoStmt->get_result()->fetch_assoc();

        if ($info) {
            Mailer::sendGradeNotification(
                $info['email'],
                $info['firstName'] . ' ' . $info['lastName'],
                $info['title'],
                (int)$score,
                (int)$maxScore,
                $grade,
                $feedback
            );
        }

        echo json_encode([
            'success' => true,
            'message' => 'Grade submitted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit grade: ' . $stmt->error
        ]);
    }

    $stmt->close();
}
?>

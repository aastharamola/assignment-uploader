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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ]);
    exit();
}

$assignmentId = sanitize($_POST['assignmentId'] ?? '');

if (empty($assignmentId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Assignment ID is required'
    ]);
    exit();
}


if (!isset($_FILES['file'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded'
    ]);
    exit();
}

$file = $_FILES['file'];


$maxSize = 50 * 1024 * 1024; 
$allowedMimes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/zip'
];

if ($file['size'] > $maxSize) {
    echo json_encode([
        'success' => false,
        'message' => 'File size exceeds 50MB limit'
    ]);
    exit();
}

if (!in_array($file['type'], $allowedMimes)) {
    echo json_encode([
        'success' => false,
        'message' => 'File type not allowed'
    ]);
    exit();
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Upload error: ' . $file['error']
    ]);
    exit();
}


$uploadDir = __DIR__ . '/../uploads/' . date('Y/m/d');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileName = $assignmentId . '_' . $userId . '_' . time() . '_' . basename($file['name']);
$filePath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save file'
    ]);
    exit();
}

$originalFileName = sanitize($file['name']);
$relativeFilePath = substr($filePath, strlen(__DIR__ . '/../'));

$stmt = $conn->prepare("SELECT id FROM submissions WHERE assignmentId = ? AND studentId = ?");
$stmt->bind_param("ii", $assignmentId, $userId);
$stmt->execute();
$existing = $stmt->get_result();

if ($existing->num_rows > 0) {
    $submission = $existing->fetch_assoc();
    $submissionId = $submission['id'];
    $stmt = $conn->prepare("UPDATE submissions SET fileName = ?, filePath = ?, submittedAt = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $originalFileName, $relativeFilePath, $submissionId);
} else {
    $stmt = $conn->prepare("INSERT INTO submissions (assignmentId, studentId, fileName, filePath) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $assignmentId, $userId, $originalFileName, $relativeFilePath);
}

if ($stmt->execute()) {
    if ($existing->num_rows > 0) {
        $submissionId = (int)$submissionId;
    } else {
        $submissionId = (int)$stmt->insert_id;
    }

    $plagiarism = analyzeSubmissionPlagiarism($conn, (int)$assignmentId, (int)$userId, $submissionId, $filePath);

    $score = $plagiarism['score'];
    $status = $plagiarism['status'];
    $matchedSubmissionId = $plagiarism['matchedSubmissionId'];

    $stmt2 = $conn->prepare("UPDATE submissions SET plagiarismScore = ?, plagiarismStatus = ?, matchedSubmissionId = ? WHERE id = ?");
    $stmt2->bind_param("dsii", $score, $status, $matchedSubmissionId, $submissionId);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'fileName' => $originalFileName,
        'filePath' => $relativeFilePath,
        'plagiarismScore' => round($score, 2),
        'plagiarismStatus' => $status,
        'matchedSubmissionId' => $matchedSubmissionId
    ]);
} else {
    unlink($filePath);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to save submission: ' . $stmt->error
    ]);
}

$stmt->close();

function analyzeSubmissionPlagiarism($conn, $assignmentId, $studentId, $submissionId, $currentAbsoluteFilePath) {
    $currentText = extractTextForPlagiarism($currentAbsoluteFilePath);
    if (trim($currentText) === '') {
        return [
            'score' => 0,
            'status' => 'pending',
            'matchedSubmissionId' => null
        ];
    }

    $bestScore = 0;
    $bestMatchId = null;

    $stmt = $conn->prepare("SELECT id, filePath FROM submissions WHERE assignmentId = ? AND id != ? AND studentId != ?");
    $stmt->bind_param("iii", $assignmentId, $submissionId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $existingAbsolutePath = __DIR__ . '/../' . $row['filePath'];
        if (!file_exists($existingAbsolutePath)) {
            continue;
        }

        $existingText = extractTextForPlagiarism($existingAbsolutePath);
        if (trim($existingText) === '') {
            continue;
        }

        $score = calculateSimilarityScore($currentText, $existingText);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatchId = (int)$row['id'];
        }
    }

    $stmt->close();

    $status = 'clean';
    if ($bestMatchId === null) {
        $bestScore = 0;
    } elseif ($bestScore >= 70) {
        $status = 'suspected';
    }

    return [
        'score' => round($bestScore, 2),
        'status' => $status,
        'matchedSubmissionId' => $bestMatchId
    ];
}

function extractTextForPlagiarism($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if ($extension === 'docx') {
        return extractTextFromDocx($filePath);
    }

    if ($extension === 'pdf') {
        return extractTextFromPdf($filePath);
    }

    return '';
}

function extractTextFromDocx($filePath) {
    if (!class_exists('ZipArchive')) {
        return '';
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        return '';
    }

    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xmlContent === false) {
        return '';
    }

    $text = strip_tags($xmlContent);
    return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function extractTextFromPdf($filePath) {
    $content = @file_get_contents($filePath);
    if ($content === false || $content === '') {
        return '';
    }

    preg_match_all('/\\((.*?)\\)/s', $content, $matches);
    if (empty($matches[1])) {
        return '';
    }

    $parts = array_map('stripcslashes', $matches[1]);
    return implode(' ', $parts);
}

function calculateSimilarityScore($textA, $textB) {
    $normalizedA = normalizeTextForPlagiarism($textA);
    $normalizedB = normalizeTextForPlagiarism($textB);

    if ($normalizedA === '' || $normalizedB === '') {
        return 0;
    }

    $wordsA = preg_split('/\s+/', $normalizedA, -1, PREG_SPLIT_NO_EMPTY);
    $wordsB = preg_split('/\s+/', $normalizedB, -1, PREG_SPLIT_NO_EMPTY);

    $shinglesA = buildShingles($wordsA, 5);
    $shinglesB = buildShingles($wordsB, 5);

    if (empty($shinglesA) || empty($shinglesB)) {
        return 0;
    }

    $intersectionCount = count(array_intersect_key($shinglesA, $shinglesB));
    $unionCount = count($shinglesA + $shinglesB);

    if ($unionCount === 0) {
        return 0;
    }

    return ($intersectionCount / $unionCount) * 100;
}

function buildShingles($words, $size) {
    $count = count($words);
    if ($count === 0) {
        return [];
    }

    if ($count < $size) {
        $size = 1;
    }

    $shingles = [];
    for ($i = 0; $i <= $count - $size; $i++) {
        $piece = implode(' ', array_slice($words, $i, $size));
        $shingles[$piece] = true;
    }

    return $shingles;
}

function normalizeTextForPlagiarism($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]+/i', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}
?>

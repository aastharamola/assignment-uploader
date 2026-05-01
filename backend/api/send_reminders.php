<?php



require_once '../config/database.php';
require_once '../utils/Mailer.php';


$sql = "SELECT a.id, a.title, a.subject, a.dueDate
        FROM assignments a
        WHERE a.dueDate BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)";

$assignments = $conn->query($sql);

if (!$assignments || $assignments->num_rows === 0) {
    echo "No assignments due in the next 24 hours.\n";
    exit;
}

$sent  = 0;
$skipped = 0;

while ($assignment = $assignments->fetch_assoc()) {
    
    $stmt = $conn->prepare(
        "SELECT u.email, u.firstName, u.lastName
         FROM users u
         WHERE u.role = 'student'
           AND u.id NOT IN (
               SELECT s.studentId FROM submissions s WHERE s.assignmentId = ?
           )"
    );
    $stmt->bind_param("i", $assignment['id']);
    $stmt->execute();
    $students = $stmt->get_result();

    while ($student = $students->fetch_assoc()) {
        $result = Mailer::sendDeadlineReminder(
            $student['email'],
            $student['firstName'] . ' ' . $student['lastName'],
            $assignment['title'],
            $assignment['subject'],
            $assignment['dueDate']
        );

        if ($result) {
            echo "✓ Reminder sent → {$student['email']} for \"{$assignment['title']}\"\n";
            $sent++;
        } else {
            echo "✗ Failed     → {$student['email']} for \"{$assignment['title']}\"\n";
            $skipped++;
        }
    }
}

echo "\nDone. Sent: {$sent} | Failed: {$skipped}\n";


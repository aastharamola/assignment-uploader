<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    private static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_FROM;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port       = MAIL_PORT;

            
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    
    
    
    public static function sendGradeNotification(
        string $studentEmail,
        string $studentName,
        string $assignmentTitle,
        int    $score,
        int    $maxScore,
        string $grade,
        string $feedback = ''
    ): bool {
        $percentage  = round(($score / $maxScore) * 100, 1);
        $gradeColors = ['A' => '#28a745', 'B' => '#17a2b8', 'C' => '#ffc107', 'D' => '#fd7e14', 'F' => '#dc3545'];
        $gradeColor  = $gradeColors[$grade] ?? '#6c757d';
        $feedbackHtml = $feedback
            ? "<div style='background:#f8f9fa;border-left:4px solid #007bff;padding:12px 16px;margin-top:16px;border-radius:4px;'>
                 <strong>📝 Feedback:</strong><br>{$feedback}
               </div>"
            : '';
        $dashboardUrl = APP_URL . '/dashboard.html';

        $body = "
        <div style='font-family:Segoe UI,sans-serif;max-width:600px;margin:0 auto;'>
          <div style='background:linear-gradient(135deg,#007bff,#6610f2);padding:30px 24px;border-radius:12px 12px 0 0;text-align:center;'>
            <h1 style='color:#fff;margin:0;font-size:22px;'>📚 Assignment Hub</h1>
            <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;'>Grade Notification</p>
          </div>
          <div style='background:#fff;padding:28px 24px;border:1px solid #dee2e6;border-top:none;'>
            <p style='color:#212529;font-size:15px;'>Hi <strong>{$studentName}</strong>,</p>
            <p style='color:#495057;'>Your assignment has been graded. Here are your results:</p>

            <div style='background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;text-align:center;'>
              <p style='margin:0 0 4px;color:#6c757d;font-size:13px;'>Assignment</p>
              <h2 style='margin:0 0 16px;color:#212529;font-size:18px;'>{$assignmentTitle}</h2>
              <div style='display:inline-block;background:{$gradeColor};color:#fff;border-radius:50%;width:72px;height:72px;line-height:72px;font-size:28px;font-weight:bold;'>
                {$grade}
              </div>
              <p style='margin:12px 0 0;font-size:20px;font-weight:bold;color:#212529;'>{$score} / {$maxScore}</p>
              <p style='margin:4px 0 0;color:#6c757d;font-size:14px;'>{$percentage}%</p>
            </div>

            {$feedbackHtml}

            <div style='text-align:center;margin-top:28px;'>
              <a href='{$dashboardUrl}' style='background:#007bff;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;'>
                View Dashboard
              </a>
            </div>
          </div>
          <div style='background:#f8f9fa;padding:14px 24px;border-radius:0 0 12px 12px;text-align:center;border:1px solid #dee2e6;border-top:none;'>
            <p style='color:#adb5bd;font-size:12px;margin:0;'>Assignment Hub &mdash; Automated Notification</p>
          </div>
        </div>";

        return self::send($studentEmail, $studentName, "🎓 Grade Received: {$assignmentTitle}", $body);
    }

    
    
    
    public static function sendNewAssignmentNotification(
        string $studentEmail,
        string $studentName,
        string $assignmentTitle,
        string $subject,
        string $dueDate,
        string $description = ''
    ): bool {
        $formattedDue = date('D, d M Y  h:i A', strtotime($dueDate));
        $descHtml = $description
            ? "<p style='color:#495057;margin:12px 0 0;'>{$description}</p>"
            : '';
        $dashboardUrl = APP_URL . '/dashboard.html';

        $body = "
        <div style='font-family:Segoe UI,sans-serif;max-width:600px;margin:0 auto;'>
          <div style='background:linear-gradient(135deg,#28a745,#20c997);padding:30px 24px;border-radius:12px 12px 0 0;text-align:center;'>
            <h1 style='color:#fff;margin:0;font-size:22px;'>📚 Assignment Hub</h1>
            <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;'>New Assignment Posted</p>
          </div>
          <div style='background:#fff;padding:28px 24px;border:1px solid #dee2e6;border-top:none;'>
            <p style='color:#212529;font-size:15px;'>Hi <strong>{$studentName}</strong>,</p>
            <p style='color:#495057;'>A new assignment has been posted. Please check the details below:</p>

            <div style='background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;'>
              <h2 style='margin:0 0 12px;color:#212529;font-size:18px;'>📝 {$assignmentTitle}</h2>
              <p style='margin:0 0 6px;color:#6c757d;font-size:14px;'>
                <strong>Subject:</strong> {$subject}
              </p>
              <p style='margin:0;color:#dc3545;font-size:14px;'>
                <strong>⏰ Due:</strong> {$formattedDue}
              </p>
              {$descHtml}
            </div>

            <div style='text-align:center;margin-top:28px;'>
              <a href='{$dashboardUrl}' style='background:#28a745;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;'>
                View Assignment
              </a>
            </div>
          </div>
          <div style='background:#f8f9fa;padding:14px 24px;border-radius:0 0 12px 12px;text-align:center;border:1px solid #dee2e6;border-top:none;'>
            <p style='color:#adb5bd;font-size:12px;margin:0;'>Assignment Hub &mdash; Automated Notification</p>
          </div>
        </div>";

        return self::send($studentEmail, $studentName, "📝 New Assignment: {$assignmentTitle}", $body);
    }

    
    
    
    public static function sendDeadlineReminder(
        string $studentEmail,
        string $studentName,
        string $assignmentTitle,
        string $subject,
        string $dueDate
    ): bool {
        $formattedDue = date('D, d M Y  h:i A', strtotime($dueDate));
        $dashboardUrl = APP_URL . '/dashboard.html';

        $body = "
        <div style='font-family:Segoe UI,sans-serif;max-width:600px;margin:0 auto;'>
          <div style='background:linear-gradient(135deg,#fd7e14,#dc3545);padding:30px 24px;border-radius:12px 12px 0 0;text-align:center;'>
            <h1 style='color:#fff;margin:0;font-size:22px;'>📚 Assignment Hub</h1>
            <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;'>⚠️ Deadline Reminder</p>
          </div>
          <div style='background:#fff;padding:28px 24px;border:1px solid #dee2e6;border-top:none;'>
            <p style='color:#212529;font-size:15px;'>Hi <strong>{$studentName}</strong>,</p>
            <p style='color:#495057;'>This is a reminder that the following assignment deadline is approaching in <strong>less than 24 hours!</strong></p>

            <div style='background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:20px;margin:20px 0;'>
              <h2 style='margin:0 0 12px;color:#212529;font-size:18px;'>📝 {$assignmentTitle}</h2>
              <p style='margin:0 0 6px;color:#6c757d;font-size:14px;'>
                <strong>Subject:</strong> {$subject}
              </p>
              <p style='margin:0;color:#dc3545;font-size:15px;font-weight:bold;'>
                ⏰ Due: {$formattedDue}
              </p>
            </div>

            <p style='color:#6c757d;font-size:14px;'>If you have already submitted, you can ignore this message.</p>

            <div style='text-align:center;margin-top:28px;'>
              <a href='{$dashboardUrl}' style='background:#dc3545;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;'>
                Submit Now
              </a>
            </div>
          </div>
          <div style='background:#f8f9fa;padding:14px 24px;border-radius:0 0 12px 12px;text-align:center;border:1px solid #dee2e6;border-top:none;'>
            <p style='color:#adb5bd;font-size:12px;margin:0;'>Assignment Hub &mdash; Automated Notification</p>
          </div>
        </div>";

        return self::send($studentEmail, $studentName, "⚠️ Deadline Reminder: {$assignmentTitle}", $body);
    }
}


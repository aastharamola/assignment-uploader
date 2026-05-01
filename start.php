<?php

echo "╔════════════════════════════════════════╗\n";
echo "║  Assignment Uploader - Server Started  ║\n";
echo "╚════════════════════════════════════════╝\n\n";

$phpVersion = phpversion();
echo "✓ PHP Version: $phpVersion\n";

$extensions = ['mysqli', 'json', 'gd', 'mbstring'];
echo "\nChecking PHP Extensions:\n";
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "  ✓ $ext\n";
    } else {
        echo "  ✗ $ext (not loaded)\n";
    }
}

if (!is_dir('backend/uploads')) {
    mkdir('backend/uploads', 0755, true);
    echo "\n✓ Created backend/uploads directory\n";
}

echo "\n" . str_repeat("─", 42) . "\n";
echo "Testing Database Connection...\n";
echo str_repeat("─", 42) . "\n\n";

require_once 'backend/config/database.php';

if ($conn && $conn->connect_error) {
    echo "✗ Database Connection Failed\n";
    echo "  Error: " . $conn->connect_error . "\n";
} else {
    echo "✓ Database Connection Successful\n";
    echo "  Host: " . DB_HOST . "\n";
    echo "  Database: " . DB_NAME . "\n";
    echo "  User: " . DB_USER . "\n";
}

echo "\n" . str_repeat("─", 42) . "\n";
echo "SERVER INFORMATION\n";
echo str_repeat("─", 42) . "\n\n";

echo "📁 Project Root: " . __DIR__ . "\n";
echo "🌐 Frontend: http://localhost/assignment-uploader/frontend/\n";
echo "📊 phpMyAdmin: http://localhost/phpmyadmin/\n";

echo "\n" . str_repeat("─", 42) . "\n";
echo "NEXT STEPS\n";
echo str_repeat("─", 42) . "\n\n";

echo "1️⃣  Open in Browser:\n";
echo "   http://localhost/assignment-uploader/frontend/\n\n";

echo "2️⃣  Create Test Account:\n";
echo "   - First Name: Test\n";
echo "   - Last Name: User\n";
echo "   - Email: test@example.com\n";
echo "   - Roll Number: TEST001\n";
echo "   - Password: TestPass123\n";
echo "   - Role: Student\n\n";

echo "3️⃣  Login with your credentials\n\n";

echo "4️⃣  Explore the dashboard and features\n\n";

echo str_repeat("═", 42) . "\n";
echo "For issues, check the INSTALLATION.md file\n";
echo str_repeat("═", 42) . "\n";
?>
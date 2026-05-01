<?php

require_once '../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action === 'login') {
    handleLogin($conn);
} elseif ($action === 'register') {
    handleRegister($conn);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}

function handleLogin($conn) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? '');

    if (empty($email) || empty($password) || empty($role)) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        return;
    }

    $stmt = $conn->prepare("SELECT id, password, email, firstName, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        return;
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        return;
    }

    if ($role !== $user['role']) {
        echo json_encode([
            'success' => false,
            'message' => 'Wrong role selected. Please choose "' . $user['role'] . '".'
        ]);
        return;
    }

    
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'userId' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'name' => $user['firstName'],
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) 
    ]);

    $headerEncoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $payloadEncoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, 'secret_key', true);
    $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    $token = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['firstName']
        ]
    ]);

    $stmt->close();
}

function handleRegister($conn) {
    $firstName = sanitize($_POST['firstName'] ?? '');
    $lastName = sanitize($_POST['lastName'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $rollNumber = sanitize($_POST['rollNumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email) || empty($rollNumber) || empty($password) || empty($role)) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already registered'
        ]);
        $stmt->close();
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE rollNumber = ?");
    $stmt->bind_param("s", $rollNumber);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Roll number already registered'
        ]);
        $stmt->close();
        return;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, rollNumber, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $rollNumber, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful. Please login.',
            'userId' => $stmt->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed: ' . $stmt->error
        ]);
    }

    $stmt->close();
}
?>

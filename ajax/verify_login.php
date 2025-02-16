<?php
session_start();
require_once '../database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if username exists
    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $check_user->store_result();

    if ($check_user->num_rows === 0) {
        $response['message'] = "Username is not registered. Please request for an account at the SSC office.";
    } else {
        // Verify password
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $response['success'] = true;
        } else {
            $response['message'] = "Invalid password";
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);

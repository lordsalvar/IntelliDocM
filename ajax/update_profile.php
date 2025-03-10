<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_POST['action'] === 'update_picture') {
    try {
        // Create upload directory if it doesn't exist
        $uploadDir = '../uploads/profile_pictures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['profile_picture'];

        // Generate unique filename
        $fileName = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Delete old profile picture if it exists
            if (isset($_POST['old_picture']) && $_POST['old_picture'] !== 'default.png') {
                $oldFile = $uploadDir . $_POST['old_picture'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            // Update database
            $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $fileName, $_SESSION['user_id']);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'image_url' => 'uploads/profile_pictures/' . $fileName
                ]);
            } else {
                throw new Exception("Failed to update database");
            }
        } else {
            throw new Exception("Failed to upload file");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

$user_id = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

// Verify password first
$verify_query = "SELECT password FROM users WHERE id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    exit;
}

// If password is correct, proceed with update
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

$update_query = "UPDATE users SET full_name = ?, email = ?, contact = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);

if ($stmt->execute()) {
    $_SESSION['full_name'] = $full_name;
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully!',
        'data' => [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating profile']);
}

<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $description = trim($_POST['description'] ?? '');

            // Input validation
            if (empty($name) || empty($code)) {
                die('Facility name and code are required');
            }

            // Check for duplicate code
            $stmt = $conn->prepare("SELECT id FROM facilities WHERE code = ?");
            $stmt->bind_param('s', $code);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                die('A facility with this code already exists');
            }

            // Insert new facility
            $sql = "INSERT INTO facilities (name, code, description) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $name, $code, $description);

            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                echo "success";
            } else {
                die('Failed to add facility');
            }
            break;

        default:
            die('Invalid action');
    }
    exit;
}

die('Invalid request method');

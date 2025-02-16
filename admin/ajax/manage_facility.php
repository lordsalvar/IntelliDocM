<?php
session_start();
require_once '../../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $facilityId = $_POST['facilityId'] ?? null;

    if (!$facilityId) {
        echo json_encode(['success' => false, 'message' => 'Facility ID is required']);
        exit;
    }

    switch ($action) {
        case 'get':
            $stmt = $conn->prepare("SELECT * FROM facilities WHERE id = ?");
            $stmt->bind_param('i', $facilityId);
            if ($stmt->execute()) {
                $result = $stmt->get_result()->fetch_assoc();
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch facility']);
            }
            break;

        case 'update':
            $name = $_POST['name'] ?? '';
            $code = $_POST['code'] ?? '';
            $description = $_POST['description'] ?? '';

            $stmt = $conn->prepare("UPDATE facilities SET name = ?, code = ?, description = ? WHERE id = ?");
            $stmt->bind_param('sssi', $name, $code, $description, $facilityId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Facility updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update facility']);
            }
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM facilities WHERE id = ?");
            $stmt->bind_param('i', $facilityId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Facility deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete facility']);
            }
            break;
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);

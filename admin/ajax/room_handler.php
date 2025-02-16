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

    switch ($action) {
        case 'get_rooms':
            $stmt = $conn->prepare("SELECT * FROM rooms WHERE facility_id = ? ORDER BY room_number");
            $stmt->bind_param('i', $facilityId);

            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $rooms = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $rooms]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch rooms']);
            }
            break;

        case 'add_room':
            $roomNumber = $_POST['roomNumber'] ?? '';
            $capacity = intval($_POST['capacity'] ?? 30);
            $description = $_POST['description'] ?? '';

            // Validate input
            if (empty($roomNumber)) {
                echo json_encode(['success' => false, 'message' => 'Room number is required']);
                exit;
            }

            // Check if room number already exists in this facility
            $check = $conn->prepare("SELECT id FROM rooms WHERE facility_id = ? AND room_number = ?");
            $check->bind_param('is', $facilityId, $roomNumber);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Room number already exists in this facility']);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO rooms (facility_id, room_number, capacity, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isis', $facilityId, $roomNumber, $capacity, $description);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Room added successfully',
                    'room' => [
                        'id' => $stmt->insert_id,
                        'room_number' => $roomNumber,
                        'capacity' => $capacity,
                        'description' => $description
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add room']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);

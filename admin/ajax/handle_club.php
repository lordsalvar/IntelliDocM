<?php
session_start();
require_once '../../database.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Update the action handling structure
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'addClub':
        try {
            $clubName = $_POST['clubName'];
            $acronym = $_POST['acronym'];
            $type = $_POST['type'];
            $moderator = $_POST['moderator'];
            $logoPath = 'default_logo.png'; // Set default value

            // Handle logo upload if provided
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                // Update the upload directory path
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/main/IntelliDocM/uploads/club_logos/';

                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed.');
                }

                $fileName = uniqid('club_') . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                    // Store relative path in database
                    $logoPath = '/main/IntelliDocM/uploads/club_logos/' . $fileName;
                }
            }

            // Updated SQL to match your database schema
            $sql = "INSERT INTO clubs (club_name, acronym, club_type, moderator, club_logo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }

            $stmt->bind_param("sssss", $clubName, $acronym, $type, $moderator, $logoPath);

            if (!$stmt->execute()) {
                throw new Exception("Database execute error: " . $stmt->error);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("Club creation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'updateClub':
        try {
            // Basic validation
            if (!isset($_POST['clubId']) || !isset($_POST['clubName'])) {
                throw new Exception('Missing required fields');
            }

            $clubId = $_POST['clubId'];
            $clubName = $_POST['clubName'];
            $acronym = $_POST['acronym'];
            $type = $_POST['type'];
            $moderator = $_POST['moderator'];

            // First, check if club exists
            $checkSql = "SELECT club_id FROM clubs WHERE club_id = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param("i", $clubId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Club not found');
            }

            // Handle logo upload if provided
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/main/IntelliDocM/uploads/club_logos/';

                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed.');
                }

                $fileName = uniqid('club_') . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                    // Update with new logo
                    $logoPath = '/main/IntelliDocM/uploads/club_logos/' . $fileName;
                    $sql = "UPDATE clubs SET club_name = ?, acronym = ?, club_type = ?, moderator = ?, club_logo = ? WHERE club_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssi", $clubName, $acronym, $type, $moderator, $logoPath, $clubId);
                }
            } else {
                // Update without changing logo
                $sql = "UPDATE clubs SET club_name = ?, acronym = ?, club_type = ?, moderator = ? WHERE club_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $clubName, $acronym, $type, $moderator, $clubId);
            }

            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Club updated successfully'
            ]);
        } catch (Exception $e) {
            error_log("Club update error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'deleteClub':
        try {
            $clubId = $_POST['clubId'];

            // Start transaction
            $conn->begin_transaction();

            // First delete club memberships
            $sql = "DELETE FROM club_memberships WHERE club_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $clubId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to delete club memberships");
            }

            // Then delete the club
            $sql = "DELETE FROM clubs WHERE club_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $clubId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to delete club");
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

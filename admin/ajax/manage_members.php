<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'getMembers':
        try {
            $clubId = $_POST['clubId'];
            $sql = "SELECT u.*, cm.designation 
                    FROM users u 
                    JOIN club_memberships cm ON u.id = cm.user_id 
                    WHERE cm.club_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $clubId);
            $stmt->execute();
            $result = $stmt->get_result();

            $members = [];
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }

            echo json_encode(['success' => true, 'members' => $members]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'addMember':
        try {
            $conn->begin_transaction();

            // Determine role based on designation
            $role = 'client'; // default role
            if (strtolower($_POST['designation']) === 'moderator') {
                $role = 'moderator';
            } elseif (strtolower($_POST['designation']) === 'dean') {
                $role = 'dean';
            }

            // First, create the user with dynamic role
            $sql = "INSERT INTO users (full_name, username, password, email, role, contact) 
                   VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt->bind_param(
                "ssssss",
                $_POST['fullName'],
                $_POST['username'],
                $hashedPassword,
                $_POST['email'],
                $role, // Use the determined role
                $_POST['contact']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create user");
            }

            $userId = $conn->insert_id;

            // Then create club membership
            $sql = "INSERT INTO club_memberships (club_id, user_id, designation) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iis",
                $_POST['clubId'],
                $userId,
                $_POST['designation']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create club membership");
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'updateMember':
        try {
            $conn->begin_transaction();

            // Determine role based on designation
            $role = 'client';
            if (strtolower($_POST['designation']) === 'moderator') {
                $role = 'moderator';
            } elseif (strtolower($_POST['designation']) === 'dean') {
                $role = 'dean';
            }

            // Update user details
            $sql = "UPDATE users SET 
                    full_name = ?, 
                    email = ?, 
                    contact = ?,
                    role = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssi",
                $_POST['fullName'],
                $_POST['email'],
                $_POST['contact'],
                $role,
                $_POST['userId']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to update user");
            }

            // Update club membership designation
            $sql = "UPDATE club_memberships SET designation = ? 
                    WHERE user_id = ? AND club_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sii",
                $_POST['designation'],
                $_POST['userId'],
                $_POST['clubId']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to update membership");
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'removeMember':
        try {
            $userId = $_POST['userId'];
            $clubId = $_POST['clubId'];

            $sql = "DELETE FROM club_memberships WHERE user_id = ? AND club_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $clubId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to remove member");
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

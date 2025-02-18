<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['clubId'])) {
    echo json_encode(['success' => false, 'message' => 'Club ID is required']);
    exit;
}

$clubId = (int)$_POST['clubId'];

try {
    // Get club details
    $club_sql = "SELECT * FROM clubs WHERE club_id = ?";
    $stmt = $conn->prepare($club_sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    $club = $stmt->get_result()->fetch_assoc();

    if (!$club) {
        throw new Exception('Club not found');
    }

    // Get club members
    $members_sql = "SELECT u.full_name, u.contact, cm.designation 
                   FROM club_memberships cm 
                   JOIN users u ON cm.user_id = u.id 
                   WHERE cm.club_id = ?";
    $stmt = $conn->prepare($members_sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'club' => $club,
        'members' => $members
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

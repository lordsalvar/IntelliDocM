<?php
session_start();
require_once '../../database.php';
require_once '../includes/club_functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['error' => 'Unauthorized access']));
}

$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'search':
            $searchTerm = $_POST['term'] ?? '';
            $response = ['success' => true, 'data' => searchClubs($searchTerm)];
            break;

        case 'add_club':
            $success = addClub(
                $_POST['clubName'],
                $_POST['acronym'],
                $_POST['type'],
                $_POST['moderator']
            );
            $response = [
                'success' => $success,
                'message' => $success ? 'Club added successfully' : 'Failed to add club',
                'stats' => getMembershipStats()
            ];
            break;

        case 'delete_club':
            $success = deleteClub($_POST['clubId']);
            $response = [
                'success' => $success,
                'message' => $success ? 'Club deleted successfully' : 'Failed to delete club',
                'stats' => getMembershipStats()
            ];
            break;

        case 'get_club':
            $clubId = $_POST['clubId'];
            $clubDetails = getClubDetails($clubId);
            $response = [
                'success' => true,
                'data' => $clubDetails
            ];
            break;

        case 'update_club':
            $success = updateClub(
                $_POST['clubId'],
                $_POST['clubName'],
                $_POST['acronym'],
                $_POST['type'],
                $_POST['moderator']
            );
            $response = [
                'success' => $success,
                'message' => $success ? 'Club updated successfully' : 'Failed to update club'
            ];
            break;
    }
}

header('Content-Type: application/json');
echo json_encode($response);

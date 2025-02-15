<?php


function fetchAllClubs()
{
    $conn = getDbConnection();
    $sql = "SELECT * FROM clubs ORDER BY club_name ASC";
    $result = $conn->query($sql);
    $clubs = [];
    while ($row = $result->fetch_assoc()) {
        $row['member_count'] = getClubMemberCount($row['club_id']);
        $clubs[] = $row;
    }
    return $clubs;
}

function getClubMemberCount($clubId)
{
    $conn = getDbConnection();
    $sql = "SELECT COUNT(*) as count FROM club_memberships WHERE club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

function addClub($clubName, $acronym, $type, $moderator)
{
    $conn = getDbConnection();
    $sql = "INSERT INTO clubs (club_name, acronym, club_type, moderator) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $clubName, $acronym, $type, $moderator);
    return $stmt->execute();
}

function updateClub($clubId, $clubName, $acronym, $type, $moderator)
{
    $conn = getDbConnection();
    $sql = "UPDATE clubs SET club_name = ?, acronym = ?, club_type = ?, moderator = ? WHERE club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $clubName, $acronym, $type, $moderator, $clubId);
    return $stmt->execute();
}

function deleteClub($clubId)
{
    $conn = getDbConnection();
    $conn->begin_transaction();
    try {
        // Delete club memberships first
        $sql1 = "DELETE FROM club_memberships WHERE club_id = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("i", $clubId);
        $stmt1->execute();

        // Then delete the club
        $sql2 = "DELETE FROM clubs WHERE club_id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $clubId);
        $stmt2->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function getClubMembers($clubId)
{
    $conn = getDbConnection();
    $sql = "SELECT u.*, cm.designation 
            FROM users u 
            JOIN club_memberships cm ON u.id = cm.user_id 
            WHERE cm.club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    return $stmt->get_result();
}

function addMember($userId, $clubId, $designation)
{
    $conn = getDbConnection();
    $sql = "INSERT INTO club_memberships (user_id, club_id, designation) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $userId, $clubId, $designation);
    return $stmt->execute();
}

function updateMember($userId, $clubId, $designation)
{
    $conn = getDbConnection();
    $sql = "UPDATE club_memberships SET designation = ? WHERE user_id = ? AND club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $designation, $userId, $clubId);
    return $stmt->execute();
}

function removeMember($userId, $clubId)
{
    $conn = getDbConnection();
    $sql = "DELETE FROM club_memberships WHERE user_id = ? AND club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $clubId);
    return $stmt->execute();
}

function searchClubs($searchTerm)
{
    $conn = getDbConnection();
    $search = "%$searchTerm%";
    $sql = "SELECT *, 
            (SELECT COUNT(*) FROM club_memberships WHERE club_id = clubs.club_id) as member_count 
            FROM clubs 
            WHERE club_name LIKE ? OR acronym LIKE ? OR club_type LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getClubDetails($clubId)
{
    $conn = getDbConnection();
    $sql = "SELECT * FROM clubs WHERE club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getMembershipStats()
{
    $conn = getDbConnection();
    return [
        'total_clubs' => $conn->query("SELECT COUNT(*) FROM clubs")->fetch_row()[0],
        'total_members' => $conn->query("SELECT COUNT(DISTINCT user_id) FROM club_memberships")->fetch_row()[0],
        'total_moderators' => $conn->query("SELECT COUNT(DISTINCT moderator) FROM clubs")->fetch_row()[0]
    ];
}

// Add more functions as needed

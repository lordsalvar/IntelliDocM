<?php
session_start();
if ($_SESSION['role'] !== 'moderator') {
    header('Location: login.php');
    exit();
}

require_once '../database.php';

// Fetch the club ID of the logged-in moderator
function getModeratorClub()
{
    global $conn;
    $moderatorId = $_SESSION['user_id'];
    $sql = "SELECT club_id FROM club_memberships WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $moderatorId);
    $stmt->execute();
    $result = $stmt->get_result();
    $club = $result->fetch_assoc();
    $stmt->close();
    return $club['club_id'] ?? null;
}

$moderatorClubId = getModeratorClub();
if (!$moderatorClubId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No club assigned to this moderator']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'fetchUsers':
                handleFetchUsers($moderatorClubId);
                break;
            case 'addUser':
                handleAddUser($moderatorClubId);
                break;
            case 'editUser':
                handleEditUser($moderatorClubId);
                break;
            case 'deleteUser':
                handleDeleteUser($moderatorClubId);
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
                break;
        }
    }
    exit();
}

function handleFetchUsers($clubId)
{
    global $conn;
    $sql = "SELECT users.id, users.full_name, users.username, users.email, users.contact, club_memberships.designation 
            FROM club_memberships 
            JOIN users ON club_memberships.user_id = users.id 
            WHERE club_memberships.club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
    echo json_encode($users);
    exit();
}

function handleAddUser($clubId)
{
    global $conn;
    $username = $_POST['username'];
    $email = $_POST['email'];
    $fullName = $_POST['full_name'];
    $designation = $_POST['designation'];
    $contact = $_POST['contact'];
    $defaultPassword = password_hash("123", PASSWORD_DEFAULT);
    $defaultRole = 'client';

    $sql = "INSERT INTO users (username, email, full_name, contact, password, role) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $username, $email, $fullName, $contact, $defaultPassword, $defaultRole);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;

        $membershipSql = "INSERT INTO club_memberships (user_id, club_id, designation) VALUES (?, ?, ?)";
        $membershipStmt = $conn->prepare($membershipSql);
        $membershipStmt->bind_param("iis", $userId, $clubId, $designation);

        if ($membershipStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign user to club']);
        }
        $membershipStmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
    $stmt->close();
    exit();
}

function handleEditUser($clubId)
{
    global $conn;
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $designation = $_POST['designation'];

    $sql = "UPDATE users SET username = ?, email = ?, contact = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $username, $email, $contact, $userId);

    if ($stmt->execute()) {
        $membershipSql = "UPDATE club_memberships SET designation = ? WHERE user_id = ? AND club_id = ?";
        $membershipStmt = $conn->prepare($membershipSql);
        $membershipStmt->bind_param("sii", $designation, $userId, $clubId);
        $membershipStmt->execute();
        $membershipStmt->close();

        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    $stmt->close();
    exit();
}

function handleDeleteUser($clubId)
{
    global $conn;
    $userId = $_POST['user_id'];

    $membershipSql = "DELETE FROM club_memberships WHERE user_id = ? AND club_id = ?";
    $membershipStmt = $conn->prepare($membershipSql);
    $membershipStmt->bind_param("ii", $userId, $clubId);
    $membershipStmt->execute();
    $membershipStmt->close();

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
    $stmt->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <?php include '../includes/moderatornav.php' ?>
    <hr>
    <hr>
    <hr>
    <div class="container mt-4">
        <h1>User Management</h1>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
        <hr>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Designation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <tr>
                    <td colspan="6" class="text-center">Loading users...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addUserForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addFullName" class="form-label">Full Name:</label>
                        <input type="text" id="addFullName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="addUsername" class="form-label">Username:</label>
                        <input type="text" id="addUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="addEmail" class="form-label">Email:</label>
                        <input type="email" id="addEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="addContact" class="form-label">Contact:</label>
                        <input type="text" id="addContact" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="addDesignation" class="form-label">Designation:</label>
                        <input type="text" id="addDesignation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add User</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editUserForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUserId">
                    <div class="mb-3">
                        <label for="editFullName" class="form-label">Full Name:</label>
                        <input type="text" id="editFullName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username:</label>
                        <input type="text" id="editUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email:</label>
                        <input type="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editContact" class="form-label">Contact:</label>
                        <input type="text" id="editContact" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDesignation" class="form-label">Designation:</label>
                        <input type="text" id="editDesignation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            function fetchUsers() {
                $.post("usermanagement.php", {
                    action: "fetchUsers"
                }, function(data) {
                    $("#userTableBody").empty();
                    if (data.length === 0) {
                        $("#userTableBody").html('<tr><td colspan="6" class="text-center">No users found</td></tr>');
                    } else {
                        data.forEach(user => {
                            $("#userTableBody").append(`
                        <tr>
                            <td>${user.full_name}</td>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>${user.contact}</td>
                            <td>${user.designation}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editUser(${user.id}, '${user.full_name}', '${user.username}', '${user.email}', '${user.contact}', '${user.designation}')">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                            </td>
                        </tr>
                    `);
                        });
                    }
                }, "json");
            }

            function deleteUser(userId) {
                if (confirm("Are you sure you want to delete this user?")) {
                    $.post("usermanagement.php", {
                        action: "deleteUser",
                        user_id: userId
                    }, function(response) {
                        if (response.success) {
                            alert("User deleted successfully");
                            fetchUsers();
                        } else {
                            alert(response.message || "Failed to delete user");
                        }
                    }, "json");
                }
            }

            // Attach functions to the global window object
            window.editUser = function(id, fullName, username, email, contact, designation) {
                // Populate modal fields with user data
                $("#editUserId").val(id);
                $("#editFullName").val(fullName);
                $("#editUsername").val(username);
                $("#editEmail").val(email);
                $("#editContact").val(contact);
                $("#editDesignation").val(designation);

                // Show the modal
                $("#editUserModal").modal("show");
            };

            $("#editUserForm").submit(function(e) {
                e.preventDefault();
                const data = {
                    action: "editUser",
                    user_id: $("#editUserId").val(),
                    full_name: $("#editFullName").val(),
                    username: $("#editUsername").val(),
                    email: $("#editEmail").val(),
                    contact: $("#editContact").val(),
                    designation: $("#editDesignation").val()
                };
                $.post("usermanagement.php", data, function(response) {
                    if (response.success) {
                        alert("User updated successfully");
                        $("#editUserModal").modal("hide");
                        fetchUsers();
                    } else {
                        alert(response.message || "Failed to update user");
                    }
                }, "json");
            });

            $("#addUserForm").submit(function(e) {
                e.preventDefault();
                const data = {
                    action: "addUser",
                    full_name: $("#addFullName").val(),
                    username: $("#addUsername").val(),
                    email: $("#addEmail").val(),
                    contact: $("#addContact").val(),
                    designation: $("#addDesignation").val()
                };
                $.post("usermanagement.php", data, function(response) {
                    if (response.success) {
                        alert("User added successfully");
                        $("#addUserModal").modal("hide");
                        fetchUsers();
                    } else {
                        alert(response.message || "Failed to add user");
                    }
                }, "json");
            });

            fetchUsers();
        });
    </script>
</body>

<?php include '../includes/footer.php' ?>

</html>
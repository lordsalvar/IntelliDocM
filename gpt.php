<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'fetchClubs') {
        echo json_encode(fetchAllClubs());
    } elseif (isset($_POST['selectedClub'])) {
        handleFetchUsers($_POST['selectedClub']);
    } elseif (isset($_POST['action']) && $_POST['action'] === 'addUser') {
        handleAddUser();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add') {
        handleAddClub();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'editUser') {
        handleEditUser();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'deleteUser') {
        handleDeleteUser();
    }
    exit();
}

function fetchAllClubs()
{
    $conn = getDbConnection();
    $sql = "SELECT club_id, club_name FROM clubs";
    $result = $conn->query($sql);
    $clubs = [];
    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }
    $conn->close();
    return $clubs;
}

function handleFetchUsers($clubId)
{
    $conn = getDbConnection();
    $sql = "SELECT users.username, users.email, users.id, club_memberships.designation 
            FROM club_memberships 
            JOIN users ON club_memberships.user_id = users.id 
            WHERE club_memberships.club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'username' => $row['username'],
            'email' => $row['email'],
            'id' => $row['id'],
            'designation' => $row['designation']
        ];
    }
    echo json_encode($users);
    $stmt->close();
    $conn->close();
}

function handleAddUser()
{
    $conn = getDbConnection();
    $username = $_POST['username'];
    $email = $_POST['email'];
    $clubId = $_POST['club_id'];
    $designation = $_POST['designation'];

    $sql = "INSERT INTO users (username, email) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $membershipSql = "INSERT INTO club_memberships (user_id, club_id, designation) VALUES (?, ?, ?)";
        $membershipStmt = $conn->prepare($membershipSql);
        $membershipStmt->bind_param("iis", $userId, $clubId, $designation);
        if ($membershipStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add user to club membership']);
        }
        $membershipStmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }

    $stmt->close();
    $conn->close();
}

function handleEditUser()
{
    $conn = getDbConnection();
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $username, $password, $userId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user']);
    }
    $stmt->close();
    $conn->close();
}

function handleDeleteUser()
{
    $conn = getDbConnection();
    $userId = $_POST['user_id'];

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
    }
    $stmt->close();
    $conn->close();
}

function handleAddClub()
{
    $conn = getDbConnection();
    $clubName = $_POST['clubName'];
    $type = $_POST['type'];

    $sql = "INSERT INTO clubs (club_name, club_type) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $clubName, $type);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Club added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not add club']);
    }

    $stmt->close();
    $conn->close();
} ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Club Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.5.4/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            fetchClubs();

            window.fetchUsers = function(clubId) {
                $('#userTableBody').html("<tr><td colspan='3'>Loading...</td></tr>");
                $.post("clubmanagement.php", {
                    selectedClub: clubId
                }, function(response) {
                    displayUsers(response);
                }).fail(function() {
                    $('#userTableBody').html("<tr><td colspan='3' class='text-danger'>Server error. Try again later.</td></tr>");
                });
            }

            function fetchClubs() {
                $.post("clubmanagement.php", {
                    action: 'fetchClubs'
                }, function(response) {
                    const clubs = JSON.parse(response);
                    const clubSelect = $('select[name="selectedClub"], #club_id');
                    clubSelect.empty();
                    clubSelect.append('<option value="" disabled selected>Select a club</option>');
                    clubs.forEach(club => {
                        clubSelect.append(`<option value="${club.club_id}">${club.club_name}</option>`);
                    });
                }).fail(function() {
                    console.error('Failed to fetch updated club list.');
                });
            }

            function displayUsers(response) {
                const userTableBody = $('#userTableBody').empty();
                try {
                    const users = JSON.parse(response);
                    if (users.error) {
                        userTableBody.html("<tr><td colspan='3' class='text-danger'>" + users.error + "</td></tr>");
                        return;
                    }

                    if (users.length === 0) {
                        userTableBody.append("<tr><td colspan='3'>No users found.</td></tr>");
                    } else {
                        users.forEach(user => {
                            userTableBody.append(`
                                <tr>
                                    <td>${user.username}</td>
                                    <td>${user.designation}</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="openEditModal(${user.id}, '${user.username}')">Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDeleteUser(${user.id})">Delete</button>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                } catch (e) {
                    userTableBody.html("<tr><td colspan='3' class='text-danger'>Error loading users.</td></tr>");
                }
            }

            $('#addClubForm').submit(function(event) {
                event.preventDefault();
                $.post("clubmanagement.php", {
                    action: 'add',
                    clubName: $('#clubName').val(),
                    type: $('#type').val()
                }, function(response) {
                    const data = JSON.parse(response);
                    $('#responseModal').modal('show').find('.modal-body').text(data.message);
                    fetchClubs();
                }).fail(function() {
                    $('#responseModal').modal('show').find('.modal-body').text('Unexpected response from the server.');
                });
            });

            $('#addUserForm').submit(function(event) {
                event.preventDefault();
                const selectedClubId = $('#club_id').val();
                $.post("clubmanagement.php", {
                    action: 'addUser',
                    username: $('#username').val(),
                    email: $('#email').val(),
                    club_id: selectedClubId,
                    designation: $('#designation').val()
                }, function(response) {
                    const data = JSON.parse(response);
                    $('#addUserModal').modal('hide');
                    fetchUsers(selectedClubId);
                }).fail(function() {
                    $('#responseModal').modal('show').find('.modal-body').text('Unexpected server response.');
                });
            });

            window.openEditModal = function(userId, username) {
                $('#editUserId').val(userId);
                $('#editUsername').val(username);
                $('#editUserModal').modal('show');
            }

            $('#editUserForm').submit(function(event) {
                event.preventDefault();
                $.post("clubmanagement.php", {
                    action: 'editUser',
                    user_id: $('#editUserId').val(),
                    username: $('#editUsername').val(),
                    password: $('#editPassword').val()
                }, function(response) {
                    const data = JSON.parse(response);
                    $('#editUserModal').modal('hide');
                    fetchUsers($('#selectedClub').val());
                }).fail(function() {
                    $('#responseModal').modal('show').find('.modal-body').text('Unexpected server response.');
                });
            });

            window.confirmDeleteUser = function(userId) {
                if (confirm("Are you sure you want to delete this user?")) {
                    $.post("clubmanagement.php", {
                        action: 'deleteUser',
                        user_id: userId
                    }, function(response) {
                        fetchUsers($('#selectedClub').val());
                    }).fail(function() {
                        $('#responseModal').modal('show').find('.modal-body').text('Unexpected server response.');
                    });
                }
            }
        });
    </script>
</head>

<body>
    <div class="container mt-3">
        <!-- Add Club Form -->
        <form id="addClubForm">
            <label>Club Name:</label>
            <input type="text" id="clubName" class="form-control" required>
            <label>Type:</label>
            <select id="type" class="form-control" required>
                <option value="Academic">Academic</option>
                <option value="Non-Acad">Non-Acad</option>
            </select>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>

        <hr>
        <label>Select Club:</label>
        <select name="selectedClub" class="form-control" onchange="fetchUsers(this.value)">
            <option value="" disabled selected>Select a club</option>
        </select>
        <hr>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Designation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody"></tbody>
        </table>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="addUserForm">
                        <label for="username">Username:</label>
                        <input type="text" id="username" class="form-control" required>
                        <label for="email">Email:</label>
                        <input type="email" id="email" class="form-control" required>
                        <label for="club_id">Select Club:</label>
                        <select id="club_id" class="form-control" required></select>
                        <label for="designation">Designation:</label>
                        <input type="text" id="designation" class="form-control" required>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId">
                        <label>Username:</label>
                        <input type="text" id="editUsername" class="form-control" required>
                        <label>New Password:</label>
                        <input type="password" id="editPassword" class="form-control" required>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Response Modal -->
        <div class="modal fade" id="responseModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body"></div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
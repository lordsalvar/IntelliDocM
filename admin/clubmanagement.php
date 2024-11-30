<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once '../database.php';

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
    $sql = "SELECT users.full_name, users.username, users.email, users.id, users.contact, club_memberships.designation 
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
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'username' => $row['username'],
            'email' => $row['email'],
            'contact' => $row['contact'],
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
    $fullName = $_POST['full_name']; // New field
    $clubId = $_POST['club_id'];
    $designation = $_POST['designation'];
    $contact = $_POST['contact'];
    $defaultPassword = password_hash("123", PASSWORD_DEFAULT); // Hash the default password
    $defaultRole = 'client'; // Default role set to 'client'

    // Insert user with default role 'client' and default password
    $sql = "INSERT INTO users (username, email, full_name, contact, password, role) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $username, $email, $fullName, $contact, $defaultPassword, $defaultRole);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;

        // Add user to club_memberships table
        $membershipSql = "INSERT INTO club_memberships (user_id, club_id, designation) VALUES (?, ?, ?)";
        $membershipStmt = $conn->prepare($membershipSql);
        $membershipStmt->bind_param("iis", $userId, $clubId, $designation);

        if ($membershipStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User added successfully with default password']);
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
    $email = $_POST['email']; // Retrieve email from POST data
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $contact = $_POST['contact'];
    $designation = $_POST['designation'];

    $sql = "UPDATE users SET username = ?, email = ?, password = ?, contact = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $username, $email, $password, $contact, $userId);

    if ($stmt->execute()) {
        $membershipSql = "UPDATE club_memberships SET designation = ? WHERE user_id = ?";
        $membershipStmt = $conn->prepare($membershipSql);
        $membershipStmt->bind_param("si", $designation, $userId);
        $membershipStmt->execute();
        $membershipStmt->close();

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

    // Begin transaction if multiple deletes are needed
    $conn->begin_transaction();

    // Delete user memberships
    $deleteMembershipsSql = "DELETE FROM club_memberships WHERE user_id = ?";
    $deleteMembershipStmt = $conn->prepare($deleteMembershipsSql);
    $deleteMembershipStmt->bind_param("i", $userId);
    $deleteMembershipStmt->execute();
    $deleteMembershipStmt->close();

    // Delete user
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $conn->commit(); // Commit if all operations succeed
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    } else {
        $conn->rollback(); // Rollback if any operation fails
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
    }
    $stmt->close();
    $conn->close();
}




function handleAddClub()
{
    $conn = getDbConnection();
    $clubName = $_POST['clubName'];
    $acronym = $_POST['acronym'];
    $type = $_POST['type'];
    $moderator = $_POST['moderator'];

    $sql = "INSERT INTO clubs (club_name, acronym, club_type, moderator) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $clubName, $acronym, $type, $moderator);


    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Club added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not add club']);
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Club Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.5.4/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            fetchClubs();

            window.fetchUsers = function(clubId) {
                $('#userTableBody').html("<tr><td colspan='4'>Loading...</td></tr>");
                $.post("clubmanagement.php", {
                    selectedClub: clubId
                }, function(response) {
                    displayUsers(response);
                }).fail(function() {
                    $('#userTableBody').html("<tr><td colspan='4' class='text-danger'>Server error. Try again later.</td></tr>");
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

                    if (users.length === 0) {
                        userTableBody.append("<tr><td colspan='6'>No users found.</td></tr>");
                    } else {
                        users.forEach(user => {
                            userTableBody.append(`
                    <tr>
                        <td class="text-center">${user.full_name}</td>
                        <td class="text-center">${user.username}</td>
                        <td class="text-center">${user.contact}</td>
                        <td class="text-center">${user.designation}</td>
                        <td class="text-center">${user.email}</td>
                        <td class="text-center">
                            <button class="btn btn-primary btn-sm" onclick="openEditModal(${user.id}, '${user.username}', '${user.email}', '${user.contact}', '${user.designation}')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="confirmDeleteUser(${user.id})">Remove</button>
                        </td>
                    </tr>
                `);
                        });
                    }
                } catch (e) {
                    userTableBody.html("<tr><td colspan='6' class='text-danger'>Error loading users.</td></tr>");
                }
            }



            $('#addClubForm').submit(function(event) {
                event.preventDefault();
                $.post("clubmanagement.php", {
                    action: 'add',
                    clubName: $('#clubName').val(),
                    acronym: $('#acronym').val(),
                    type: $('#type').val(),
                    moderator: $('#moderator').val()
                }, function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        fetchClubs();
                        $('#responseModal').modal('show').find('.modal-body').text(data.message);
                    } else {
                        $('#responseModal').modal('show').find('.modal-body').text(data.message);
                    }
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
                    full_name: $('#full_name').val(), // Include full name
                    email: $('#email').val(),
                    club_id: selectedClubId,
                    designation: $('#designation').val(),
                    contact: $('#contact').val()
                }, function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#addUserModal').modal('hide');
                        fetchUsers(selectedClubId); // Refresh user list for the selected club
                    } else {
                        $('#responseModal').modal('show').find('.modal-body').text(data.message);
                    }
                }).fail(function() {
                    $('#responseModal').modal('show').find('.modal-body').text('Unexpected response from the server.');
                });
            });


            window.openEditModal = function(userId, username, email, contact, designation) {
                $('#editUserId').val(userId);
                $('#editUsername').val(username);
                $('#editEmail').val(email); // Populate email field
                $('#editContact').val(contact);
                $('#editDesignation').val(designation);
                $('#editUserModal').modal('show');
            };


            $('#editUserForm').submit(function(event) {
                event.preventDefault();
                $.post("clubmanagement.php", {
                    action: 'editUser',
                    user_id: $('#editUserId').val(),
                    username: $('#editUsername').val(),
                    email: $('#editEmail').val(), // Include email in submission
                    password: $('#editPassword').val(),
                    designation: $('#editDesignation').val(),
                    contact: $('#editContact').val()
                }, function(response) {
                    const data = JSON.parse(response);
                    $('#editUserModal').modal('hide');
                    if (data.status === 'success') {
                        alert('User updated successfully.');
                    } else {
                        alert('Failed to update user: ' + data.message);
                    }
                    fetchUsers($('#selectedClub').val());
                }).fail(function() {
                    alert('Unexpected server response.');
                });
            });



            window.confirmDeleteUser = function(userId) {
                if (confirm("Are you sure you want to delete this user?")) {
                    $.post("clubmanagement.php", {
                        action: 'deleteUser',
                        user_id: userId
                    }, function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            fetchUsers($('#selectedClub').val());
                        } else {
                            $('#responseModal').modal('show').find('.modal-body').text(data.message);
                        }
                    }).fail(function() {
                        $('#responseModal').modal('show').find('.modal-body').text('Unexpected server response.');
                    });
                }
            }

        });
    </script>
</head>

<body>


    <?php include '../includes/navbar.php' ?>
    <hr>
    <hr>
    <hr>
    <div class="container mt-3">
        <h2>Add New Organization</h2>
        <form id="addClubForm">
            <div class="form-group">
                <label>Organization Name:</label>
                <input type="text" id="clubName" name="clubName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Acronym:</label>
                <input type="text" id="acronym" name="acronym" class="form-control" requied>
            </div>
            <div class="form-group">
                <label>Type:</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="Academic">Academic</option>
                    <option value="Non-Academic">Non-Academic</option>
                    <option value="ACCO">ACCO</option>
                    <option value="CSG">CSG</option>
                    <option value="College-LGU">College-LGU</option>
                </select>
            </div>
            <div class="form-group">
                <label>Moderator:</label>
                <input type="text" id="moderator" name="moderator" class="form-control" required>
            </div>
            <br>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>

        <hr>

        <h2 class="mt-3">User Management</h2>
        <div class="form-group mt-3">
            <label>Select Club:</label>
            <select name="selectedClub" class="form-control" onchange="fetchUsers(this.value)">
                <option value="" disabled selected>Select a club</option>
            </select>
        </div>


        <button type="button" class="btn btn-primary mt-3" data-toggle="modal" data-target="#addUserModal"> Add User </button>
        <hr>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center">Student Name</th>
                    <th class="text-center">Username</th>
                    <th class="text-center">Contact Number</th>
                    <th class="text-center">Designation</th>
                    <th class="text-center">Email</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody"></tbody>
        </table>

        <hr>
        <h2 class="mt-3">List of All Clubs</h2>
        <div id="clubList" class="mt-3">
            <ul class="list-group" id="allClubsList"></ul>
        </div>


        <!--ADD USER MODAL -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="addUserForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="full_name">Full Name:</label>
                                <input type="text" class="form-control" id="full_name" required>
                            </div>
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" class="form-control" id="username" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="form-group">
                                <label for="club_id">Select Club:</label>
                                <select class="form-control" id="club_id" required></select>
                            </div>
                            <div class="form-group">
                                <label for="designation">Designation:</label>
                                <input type="text" class="form-control" id="designation" required>
                            </div>
                            <div class="form-group">
                                <label for="contact">Contact Number:</label>
                                <input type="text" class="form-control" id="contact" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="editUsername">Username:</label>
                                <input type="text" class="form-control" id="editUsername" required>
                            </div>
                            <div class="form-group">
                                <label for="editEmail">Email:</label>
                                <input type="email" class="form-control" id="editEmail" required>
                            </div>
                            <div class="form-group">
                                <label for="editPassword">New Password:</label>
                                <input type="password" class="form-control" id="editPassword" required>
                            </div>
                            <div class="form-group">
                                <label for="editDesignation">Designation:</label>
                                <input type="text" class="form-control" id="editDesignation" required>
                            </div>
                            <div class="form-group">
                                <label for="editContact">Contact:</label>
                                <input type="text" class="form-control" id="editContact" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="responseModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Response</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php' ?>
</body>

</html>
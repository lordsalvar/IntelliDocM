<?php
session_start();

// Initialize users data
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [];
}

// Function to get all users
function getUsers()
{
    return $_SESSION['users'];
}

// Function to add a new user
function addUser($username, $email)
{
    $id = uniqid();
    $_SESSION['users'][$id] = [
        'id' => $id,
        'username' => $username,
        'email' => $email
    ];
}

// Function to update a user
function updateUser($id, $username, $email)
{
    if (isset($_SESSION['users'][$id])) {
        $_SESSION['users'][$id]['username'] = $username;
        $_SESSION['users'][$id]['email'] = $email;
    }
}

// Function to delete a user
function deleteUser($id)
{
    if (isset($_SESSION['users'][$id])) {
        unset($_SESSION['users'][$id]);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            addUser($_POST['username'], $_POST['email']);
        } elseif ($_POST['action'] === 'update') {
            updateUser($_POST['id'], $_POST['username'], $_POST['email']);
        } elseif ($_POST['action'] === 'delete') {
            deleteUser($_POST['id']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Club Management</h2>

        <!-- Register Club Form -->
        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Club Name:</label>
                <input type="text" name="clubName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Type:</label>
                <select name="type" class="form-control" required>
                    <option value="Academic">Academic</option>
                    <option value="Non-Acad">Non-Acad</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>

        <hr>


        <div class="container mt-5">
            <h2>User Management</h2>

            <div class="form-group">
                <label>Select Club:</label>
                <select name="type" class="form-control" required>
                    <option value="Academic">Association of Computer Studies Students</option>
                    <option value="Non-Acad">College of Computing and Information Sciences</option>
                </select>
            </div>
        </div>

        <!-- Display Users -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Designation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (getUsers() as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <form method="POST" action="users.php" style="display:inline-block;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            <button class="btn btn-info btn-sm" onclick="editUser('<?= $user['id'] ?>', '<?= $user['username'] ?>', '<?= $user['email'] ?>')">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


        <!-- Bootstrap Modal for Editing User -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="users.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editId">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="editStudentName">Student Name:</label>
                                <input type="text" name="student_name" id="editStudentName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="editUsername">Username:</label>
                                <input type="text" name="username" id="editUsername" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="editPassword">Password:</label>
                                <input type="password" name="password" id="editPassword" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-primary mt-3" data-toggle="modal" data-target="#addUserModal"> Add User </button>
    </div>


    <!-- yoU ARE NOW HERE CIGEL -->
    <!-- THIS IS FOR THE ADD USER MODAL -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add User</h5> <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="form-group"> <label for="studentName">Student Name</label> <input type="text" class="form-control" id="studentName" required> </div>
                        <div class="form-group"> <label for="username">Username</label> <input type="text" class="form-control" id="username" required> </div>
                        <div class="form-group"> <label for="password">Password</label> <input type="password" class="form-control" id="password" required> </div>
                    </form>
                </div>
                <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> <button type="submit" class="btn btn-primary" onclick="addUser()">Add User</button> </div>
            </div>


            <!-- Edit User Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="users.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="editId">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Username:</label>
                                    <input type="text" name="username" id="editUsername" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Email:</label>
                                    <input type="email" name="email" id="editEmail" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

        <script>
            function editUser(id, username, email) {
                document.getElementById('editId').value = id;
                document.getElementById('editUsername').value = username;
                document.getElementById('editEmail').value = email;
                $('#editModal').modal('show');
            }

            //function addUser(id, )
        </script>
</body>

</html>
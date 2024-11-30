<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get the database connection
    $conn = getDbConnection();

    // Fetch user details
    $stmt = $conn->prepare("
            SELECT u.id, u.password, u.role, cm.designation 
            FROM users u
            LEFT JOIN club_memberships cm ON u.id = cm.user_id
            WHERE u.username = ?
            LIMIT 1
        ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role, $designation);
    $stmt->fetch();

    // Verify user credentials
    // Verify user credentials
    if ($stmt->num_rows > 0) {
        // Debug output for fetched values
        echo "ID: $id, Role: $role, Designation: $designation<br>";

        if (password_verify($password, $hashed_password)) {
            // Store normalized designation in the session
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['designation'] = strtolower(trim($designation)); // Normalize to lowercase

            // Debugging session variables
            echo "Redirecting based on: Role = $role, Designation = " . $_SESSION['designation'] . "<br>";

            // Use normalized designation for comparison
            if ($role === 'admin') {
                header('Location: /main/IntelliDocM/admin/view_proposals.php');
                exit();
            } elseif ($_SESSION['designation'] === 'moderator') {
                header('Location: /main/IntelliDocM/moderator/moderator_view.php');
                exit();
            } elseif ($_SESSION['designation'] === 'dean') {
                header('Location: /main/IntelliDocM/dean/dean_view.php');
                exit();
            } else {
                header('Location: /main/IntelliDocM/client.php');
                exit();
            }
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<!-- Source Codes By CodingNepal - www.codingnepalweb.com -->
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Form in HTML and CSS | CodingNepal</title>
    <link rel="stylesheet" href="/main/IntelliDocM/css/login.css" />
</head>

<body>
    <div class="login_form">
        <!-- Login form container -->
        <form method="POST" action="index.php">
            <h3>Log in</h3>
            <!-- Login option separator -->
            <p class="separator">
            </p>
            <!-- Email input box -->
            <div class="input_box">
                <label for="username">Username</label>
                <input type="username" id="username" name="username" placeholder="Enter your username" required />
            </div>
            <!-- Paswwrod input box -->
            <div class="input_box">
                <div class="password_title">
                    <label for="password">Password</label>
                </div>
                <input type="password" id="password" name="password" placeholder="Enter your password" required />
            </div>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <!-- Login button -->
            <button type="submit">Log In</button>
        </form>
    </div>




</body>

</html>
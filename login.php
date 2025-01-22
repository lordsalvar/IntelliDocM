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
                    header('Location: admin/view_proposals.php');
                    exit();
                } elseif ($_SESSION['designation'] === 'moderator') {
                    header('Location: moderator/moderator_view.php');
                    exit();
                } elseif ($_SESSION['designation'] === 'dean') {
                    header('Location: dean/dean_view.php');
                    exit();
                } else {
                    header('Location: client.php');
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="css/login.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    
</head>

<body>
<section class="vh-100">
    <div class="container-fluid h-custom">
      <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-md-9 col-lg-6 col-xl-5">
          <img src="images/draw2.webp"
            class="img-fluid" alt="Sample image">
        </div>
        <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">

        <form method="POST" action="login.php">
            <div>
                <h2 class="text-center">Login</h2>
            </div>
  
            <!-- Username input -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="username" class="form-control" id="username" name="username" required placeholder="Enter your username">
            </div>
  
            <!-- Password input -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>
            
            <div class="text-center text-lg-start mt-4 pt-2">
              <button  type="submit" class="btn btn-primary btn-sm">Login</button>
            </div>
        </div>
      </div>
    </div>
    
  </section>
  
  <!-- Bootstrap JS and dependencies -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
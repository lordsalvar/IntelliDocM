<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get the database connection
    $conn = getDbConnection();
    

    // Check if the username exists
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        if ($role === 'admin') {
            // Redirect to admin page
            header('Location: admin/view_proposals.php');
        } elseif ($role === 'moderator') {
            // Redirect to moderator page
            header('Location: moderator/moderator_view.php');
        } elseif ($role === 'dean') {
            // Redirect to moderator page
            header('Location: dean/dean_view.php');
        } else {
            // Redirect to client page
            header('Location: client.php');
        }
        exit();
    } else {
        $error = "Invalid username or password";
    }

    $stmt->close();
    $conn->close();
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
        </form>
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
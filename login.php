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
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>

        <!-- Google Fonts: Poppins -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #4158D0;
                background-image: linear-gradient(43deg, #4158D0 0%, #C850C0 46%, #FFCC70 100%);
                ;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }

            .login-container {
                width: 100%;
                max-width: 400px;
                padding: 2rem;
                background-color: #85c1e9;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
            }
        </style>
    </head>

    <body>

        <div class="login-container">
            <h2 class="text-center">Login</h2>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="username" class="form-control" id="username" name="username" required placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
        </div>



        <!-- Bootstrap JS and dependencies -->
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>

    </html>
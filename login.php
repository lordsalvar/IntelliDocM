<?php
session_start();
require_once 'database.php';
include 'system_log/activity_log.php';

$error = ''; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get the database connection
    $conn = getDbConnection();

    // First check if username exists
    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $check_user->store_result();

    if ($check_user->num_rows === 0) {
        $error = "Username is not registered. Please request for an account at the SSC office.";
    } else {
        // Fetch user details
        $stmt = $conn->prepare("
            SELECT u.id, u.password, u.role, u.full_name, cm.designation 
            FROM users u
            LEFT JOIN club_memberships cm ON u.id = cm.user_id
            WHERE u.username = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $hashed_password, $role, $full_name, $designation);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $userActivity = 'User logged in';
            logActivity($username, $userActivity);

            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['designation'] = strtolower(trim($designation));

            // Redirect based on role
            if ($role === 'admin') {
                header('Location: /main/intellidocm/admin_dashboard.php');
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
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IntelliDoc</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #FFF5F5;
            font-family: Arial, sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(183, 28, 28, 0.1);
            border-left: 5px solid #B71C1C;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            color: #B71C1C;
        }

        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #B71C1C;
        }

        .form-control {
            padding-left: 3rem;
            border-radius: 8px;
            border: 1px solid rgba(183, 28, 28, 0.2);
        }

        .form-control:focus {
            border-color: #B71C1C;
            box-shadow: 0 0 0 0.2rem rgba(183, 28, 28, 0.25);
        }

        .btn-login {
            background: #B71C1C;
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            width: 100%;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #D32F2F;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }

        /* Loader Styles */
        .loader-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #B71C1C;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .loader-container.show {
            opacity: 1;
        }

        .loader-logo {
            width: 150px;
            height: auto;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
</head>

<body>
    <!-- Add Loader Container -->
    <div class="loader-container">
        <img src="css/img/cjc_logo.png" alt="CJC Logo" class="loader-logo">
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-user-circle"></i>
                <h2>Welcome Back</h2>
                <p>Sign in to continue</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        required
                        placeholder="Username">
                </div>

                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required
                        placeholder="Password">
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Login
                </button>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            // First verify credentials without showing loader
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            try {
                const response = await fetch('ajax/verify_login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Only show loader and redirect on success
                    const loader = document.querySelector('.loader-container');
                    loader.style.display = 'flex';
                    setTimeout(() => loader.classList.add('show'), 10);

                    // Submit the form after showing loader
                    setTimeout(() => {
                        this.submit();
                    }, 800);
                } else {
                    // Show error message without loader
                    const errorDiv = document.querySelector('.alert') || document.createElement('div');
                    errorDiv.className = 'alert alert-danger';
                    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${result.message}`;

                    if (!document.querySelector('.alert')) {
                        this.insertBefore(errorDiv, this.firstChild);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
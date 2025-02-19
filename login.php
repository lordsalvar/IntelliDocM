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
                header('Location: moderator_dashboard.php');
                exit();
            } elseif ($_SESSION['designation'] === 'dean') {
                header('Location: dean_dashboard.php');
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
    <title>IntelliDoc - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8B0000;
            --secondary-color: #DC3545;
            --accent-color: #FFE5E5;
            --dark-text: #2C3E50;
            --light-text: #ECF0F1;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('images/norbertbackground.png') center/cover no-repeat;
            opacity: 0.1;
            z-index: -1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 580px;
            /* Increased from 480px */
            padding: 3.5rem;
            /* Increased from 3rem */
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header img {
            width: 120px;
            /* Increased from 100px */
            height: auto;
            margin-bottom: 2rem;
            /* Increased from 1.5rem */
        }

        .login-header h2 {
            color: var(--primary-color);
            font-size: 2.5rem;
            /* Increased from 2rem */
            font-weight: 600;
            margin-bottom: 1rem;
            /* Increased from 0.75rem */
        }

        .login-header p {
            color: #666;
            font-size: 1.1rem;
            /* Increased from 0.95rem */
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 1.2rem;
            /* Increased from 1rem */
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.3rem;
            /* Increased from 1.1rem */
        }

        .form-control {
            height: 4rem;
            /* Increased from 3.5rem */
            padding-left: 3.5rem;
            /* Increased from 3rem */
            border-radius: 10px;
            border: 2px solid #E0E0E0;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            /* Increased from 1.1rem */
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.15);
        }

        .btn-login {
            height: 4rem;
            /* Increased from 3.5rem */
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1.3rem;
            /* Increased from 1.2rem */
            margin-top: 1rem;
            /* Added margin top */
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert-danger {
            background: #FFE5E5;
            color: var(--primary-color);
        }

        .alert i {
            font-size: 1.25rem;
        }

        /* Animation for form elements */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            animation: fadeInUp 0.5s ease forwards;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.1s;
        }

        .btn-login {
            animation: fadeInUp 0.5s ease forwards;
            animation-delay: 0.2s;
        }

        /* Updated Loader Styles */
        .loader-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #8B0000, #DC3545);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: all 0.5s ease;
        }

        .loader-container.show {
            opacity: 1;
        }

        .loader-content {
            text-align: center;
            animation: pulse 2s infinite;
        }

        .loader-content img {
            width: 150px;
            height: auto;
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.3));
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                filter: brightness(1);
            }

            50% {
                transform: scale(1.05);
                filter: brightness(1.2);
            }

            100% {
                transform: scale(0.95);
                filter: brightness(1);
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="images/logo.png" alt="IntelliDoc Logo">
                <h2>Welcome Back</h2>
                <p>Sign in to IntelliDoc System</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text"
                        class="form-control"
                        name="username"
                        placeholder="Enter your username"
                        required>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password"
                        class="form-control"
                        name="password"
                        placeholder="Enter your password"
                        required>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In
                </button>
            </form>
        </div>
    </div>

    <div class="loader-container">
        <div class="loader-content">
            <img src="css/img/cjc_logo.png" alt="CJC Logo">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading animation
            const loader = document.querySelector('.loader-container');
            loader.style.display = 'flex';
            setTimeout(() => loader.classList.add('show'), 10);

            // Submit the form after a brief delay
            setTimeout(() => {
                this.submit();
            }, 800);
        });
    </script>
</body>

</html>
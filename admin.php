<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <title>Admin Page</title>
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            width: 200px;
            background-color: #f8f9fa;
            padding-top: 20px;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }

        .content {
            margin-left: 220px;
            /* Same width as sidebar + some spacing */
            padding: 20px;
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>


    <h1>Welcome, Admin!</h1>
    <p>This is the admin page.</p>
</body>

</html>
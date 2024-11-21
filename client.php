<?php
session_start();
if ($_SESSION['role'] !== 'client') {
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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script>
        // Sidebar toggle script (if you have a sidebar)
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
    <title>Client Page</title>
</head>

<body>
    <?php include 'includes/clientnavbar.php'; ?>
    <h1>Welcome, Client!</h1>
    <p>This is the client page.</p>
    <a href="activity_proposal_form.php">Activity Form</a>
    <form method="POST" action="logout.php"> <button type="submit" class="btn btn-danger">Logout</button> </form>
</body>

</html>
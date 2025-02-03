<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/notif.css">
    <title>Notifications</title>
    <style>
        
    </style>
</head>
<body>
    <header>
        <?php include 'includes/clientnavbar.php'; ?>
    </header>

    <div class="container">
        <h1>Notifications</h1>
        <div class="notifications">
            <!-- Notifications will be loaded here -->
        </div>
    </div>
    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>

    <script>
        function loadNotifications() {
            fetch('fetch_notifications.php') // Make sure this points to your PHP script for fetching notifications
                .then(response => response.text())
                .then(data => {
                    document.querySelector('.notifications').innerHTML = data;
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        // Load notifications on page load
        window.addEventListener('load', loadNotifications);

        // Optionally, refresh notifications every 5 minutes
        setInterval(loadNotifications, 300000); // 300,000 milliseconds = 5 minutes
    </script>
</body>
</html>

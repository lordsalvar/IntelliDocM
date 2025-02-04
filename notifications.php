<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/notif.css">
    <script>
        function loadNotifications() {
            fetch('fetch_notifications.php')
                .then(response => response.json())
                .then(data => {
                    let notificationsDiv = document.querySelector('.notifications');
                    notificationsDiv.innerHTML = '';

                    if (data.length === 0) {
                        notificationsDiv.innerHTML = '<p>No new notifications.</p>';
                    } else {
                        data.forEach(notification => {
                            let notifElement = document.createElement('div');
                            notifElement.classList.add('notification');
                            notifElement.innerHTML = `<p>${notification.message}</p><small>${notification.created_at}</small>`;
                            notificationsDiv.appendChild(notifElement);
                        });
                    }
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        // Load notifications on page load
        window.addEventListener('load', loadNotifications);

        // Optionally, refresh notifications every 5 minutes
        setInterval(loadNotifications, 300000);
    </script>
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
</body>

</html>
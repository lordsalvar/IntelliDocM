<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/notif.css">
    <script>
        function loadNotifications() {
            let notificationsDiv = document.querySelector('.notifications');
            notificationsDiv.innerHTML = '<div class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</div>';

            fetch('fetch_notifications.php')
                .then(response => response.json())
                .then(data => {
                    notificationsDiv.innerHTML = '';

                    if (data.length === 0) {
                        notificationsDiv.innerHTML = `
                            <div class="alert alert-info text-center" role="alert">
                                No new notifications.
                            </div>`;
                    } else {
                        data.forEach(notification => {
                            let notifElement = document.createElement('div');
                            notifElement.classList.add('card', 'mb-3', 'shadow-sm');

                            notifElement.innerHTML = `
                                <div class="card-body">
                                    <h6 class="card-title text-primary"><i class="bi bi-bell"></i> Notification</h6>
                                    <p class="card-text">${notification.message}</p>
                                    <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
                                </div>
                            `;
                            notificationsDiv.appendChild(notifElement);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    notificationsDiv.innerHTML = `
                        <div class="alert alert-danger text-center" role="alert">
                            Failed to load notifications.
                        </div>`;
                });
        }

        // Load notifications on page load
        window.addEventListener('load', loadNotifications);

        // Auto-refresh every 5 minutes
        setInterval(loadNotifications, 300000);
    </script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 800px;
            margin-top: 30px;
        }

        .card {
            border-left: 5px solid #007bff;
            border-radius: 8px;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <header>
        <?php include 'includes/clientnavbar.php'; ?>
    </header>
    <hr>
    <div class="container">
        <h2 class="text-center mb-4">🔔 Notifications</h2>
        <div class="notifications">
            <!-- Notifications will be loaded here -->
        </div>
    </div>

    <footer class="text-center mt-4 mb-2">
        <?php include 'includes/footer.php'; ?>
    </footer>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</body>

</html>
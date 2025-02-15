<?php
// Calculate the base path relative to the current script
$basePath = dirname($_SERVER['SCRIPT_NAME']);

// Ensure the base path ends with a forward slash
if (substr($basePath, -1) !== '/') {
    $basePath .= '/';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Navbar with Offcanvas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>

    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light fixed-top" style="background-color: #bc000b;">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="#">IntelliDoc</a>
            <button class="navbar-toggler text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/main/IntelliDocM/client.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="booking/calendar.php">Calendar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="facilityManager.php">Facility Manager</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="ActivityTracker.php">Activity Tracker</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/main/IntelliDocM/activity_proposal_form.php">Student Forms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/main/IntelliDocM/notifications.php">Notifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/main/IntelliDocM/logout.php" onclick="return confirmLogout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Offcanvas for small screens -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header" style="background-color: #e31b23;">
            <h5 class="offcanvas-title text-white" id="offcanvasNavbarLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item">
                    <a class="nav-link" href="/main/IntelliDocM/client.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/main/IntelliDocM/booking/facilityBooking.php">Facility Request</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/main/IntelliDocM/activity_proposal_form.php">Student Forms</a>
                </li>
                <li class="nav-item">
                    <button type="button" class="btn btn-red text-white position-relative" data-bs-toggle="modal" data-bs-target="#notificationModal">
                        Notification
                        <?php if (!empty($notifications)): ?>
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                <?= count($notifications) ?>
                            </span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/main/IntelliDocM/logout.php" onclick="return confirmLogout()">Logout</a>
                </li>
            </ul>
        </div>
    </div>

    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }
    </script>


    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<script>
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const url = this.getAttribute('href');

            // Fetch notifications for the selected page
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    document.querySelector('.modal-content').innerHTML = data;
                })
                .catch(error => console.error('Error fetching notifications:', error));
        });
    });
</script>

</html>
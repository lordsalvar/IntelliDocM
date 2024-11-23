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
            .offcanvas {
                background-color: #e31b23;
            }

            .offcanvas .nav-link {
                color: white !important;
            }

            .offcanvas .navbar-brand {
                color: white !important;
            }

            .navbar .nav-link {
                color: white !important;
            }

            .offcanvas-title {
                color: white !important;
            }

            .navbar-toggler {
                border: 2px solid white !important;
                /* Adds a white border */
                color: white !important;
                /* Ensures the icon is white */
            }
        </style>
    </head>

    <body>
        <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #e31b23;">
            <div class="container-fluid">
                <a class="navbar-brand text-white" href="#">IntelliDoc</a>
                <button class="navbar-toggler text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="client.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="public/forms.php">Student Activities Forms</a>
                        </li>
                        <li class="nav-item">
                            <?php include 'notifModal.php'; ?>
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#notificationModal">
                                Notification
                            </button>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Offcanvas for small screens -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item">
                        <a class="nav-link" href="client.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/forms.php">Student Activities Forms</a>
                    </li>
                    <li class="nav-item">
                        <?php include 'notifModal.php'; ?>
                        <button type="button" class="btn btn-light w-100 mt-2" data-bs-toggle="modal" data-bs-target="#notificationModal">
                            Notification
                        </button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bootstrap JS and dependencies -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>
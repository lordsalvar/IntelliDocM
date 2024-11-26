<?php
// Calculate the base path relative to the current script
$basePath = dirname($_SERVER['SCRIPT_NAME']);

// Ensure the base path ends with a forward slash
if (substr($basePath, -1) !== '/') {
    $basePath .= '/';
}
?>
<nav class="navbar navbar-expand-lg navbar-light fixed-top" style="background-color: #e31b23;">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-2" id="sidebarToggle">☰</button>
        <a class="navbar-brand text-white" href="#">IntelliDoc</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $basePath; ?>dean/dean_view.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $basePath; ?>usermanagement.php">User Managment</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $basePath; ?>calendar.php">Calendar</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
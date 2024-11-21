<?php
// Calculate the base path relative to the current script
$basePath = dirname($_SERVER['SCRIPT_NAME']);

// Ensure the base path ends with a forward slash
if (substr($basePath, -1) !== '/') {
    $basePath .= '/';
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary me-2" id="sidebarToggle">☰</button>
        <a class="navbar-brand" href="#">IntelliDoc</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="admin.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="clubmanagement.php">Club Management</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>
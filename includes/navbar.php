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
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $basePath; ?>view_proposals.php">Home</a></li>
                <li class="nav-item0"><a class="nav-link text-white" href="<?php echo $basePath; ?>clubmanagement.php">Club Management</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $basePath; ?>../calendar/calendar.php">Calendar</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $basePath; ?>../admin/admin_panel.php">Block Requests</a></li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/main/IntelliDocM/logout.php" onclick="return confirmLogout()">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    function confirmLogout() {
        return confirm("Are you sure you want to log out?");
    }
</script>
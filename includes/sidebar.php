<div class="sidebar">
    <div class="profile">
        <img src="css/img/cjc_logo.png" alt="Profile">
        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    </div>
    <nav>
        <a href="client_dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'client_dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="documents.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : '' ?>">
            <i class="fas fa-file"></i>
            <span>Documents</span>
        </a>
        <a href="activity_calendar.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'activity_calendar.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Activity Calendar</span>
        </a>
        <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>
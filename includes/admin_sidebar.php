<div class="sidebar">
    <div class="profile">
        <img src="../css/img/cjc_logo.png" alt="Admin Profile" class="profile-img">
        <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator'); ?></span>
    </div>
    <nav>
        <a href="/main/intellidocm/admin_dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin/manage_users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Manage Users</span>
        </a>
        <a href="admin/view_proposals.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'view_proposals.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i>
            <span>Proposals</span>
        </a>
        <a href="/main/intellidocm/admin/manage_club.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_clubs.php' ? 'active' : '' ?>">
            <i class="fas fa-building"></i>
            <span>Manage Clubs</span>
        </a>
        <a href="/main/intellidocm/admin/facility_management.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'facility_management.php' ? 'active' : '' ?>">
            <i class="fas fa-door-open"></i>
            <span>Resource Management</span>
        </a>
        <a href="/main/intellidocm/admin_activity_calendar.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_activity_calendar.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Activity Calendar</span>
        </a>
        <a href="admin/system_logs.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'system_logs.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            <span>System Logs</span>
        </a>
        <a href="admin/settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="/main/IntelliDocM/logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>
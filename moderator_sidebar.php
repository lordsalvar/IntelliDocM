<div class="sidebar">
    <div class="profile">
        <img src="css/img/cjc_logo.png" alt="User Profile" class="profile-img">
        <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
    </div>
    <nav>
        <a href="moderator_dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'moderator_dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="moderator_viewProposal.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'moderator_viewProposals.php' ? 'active' : '' ?>">
            <i class="fas fa-eye"></i>
            <span>View Proposals</span>
        </a>
        <a href="facility_reservation.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'facility_reservation.php' ? 'active' : '' ?>">
            <i class="fas fa-building"></i>
            <span>Reserve Facility</span>
        </a>
        <a href="client_notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'moderator_notifications.php' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="/main/intellidocm/profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile_settings.php' ? 'active' : '' ?>">
            <i class="fas fa-user-cog"></i>
            <span>Profile Settings</span>
        </a>
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>
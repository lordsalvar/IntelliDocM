<?php
session_start();
require_once 'database.php';

// Validate user login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    $update_query = "UPDATE users SET full_name = ?, email = ?, contact = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - IntelliDoc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/profile.css">
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        <div class="content">
            <div class="profile-container">
                <div class="profile-header">
                    <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                    <?php if (isset($success_message)): ?>
                        <div class="alert success">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form id="profileForm" class="profile-form" onsubmit="return handleProfileUpdate(event)">
                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" id="full_name" name="full_name"
                            value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email"
                            value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone"
                            value="<?php echo htmlspecialchars($user_data['contact']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user-tag"></i> Username
                        </label>
                        <input type="text" id="username"
                            value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
                        <small>Username cannot be changed</small>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="password-btn" onclick="openPasswordModal()">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Verification Modal -->
            <div id="verificationModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-shield-alt"></i> Verify Changes</h3>
                        <span class="close" onclick="closeVerificationModal()">&times;</span>
                    </div>
                    <p class="verify-text">Please enter your password to save these changes:</p>
                    <div class="form-group">
                        <label for="verifyPassword">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" id="verifyPassword" required>
                    </div>
                    <div class="modal-buttons">
                        <button onclick="confirmChanges()" class="save-btn">
                            <i class="fas fa-check"></i> Confirm Changes
                        </button>
                        <button onclick="closeVerificationModal()" class="cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Change Password</h3>
                <span class="close">&times;</span>
            </div>
            <form id="passwordChangeForm" onsubmit="return handlePasswordChange(event)">
                <div class="form-group">
                    <label for="currentPassword">
                        <i class="fas fa-lock"></i> Current Password
                    </label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">
                        <i class="fas fa-key"></i> New Password
                    </label>
                    <input type="password" id="newPassword" name="newPassword" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">
                        <i class="fas fa-check-circle"></i> Confirm New Password
                    </label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="save-btn">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                    <button type="button" class="cancel-btn" onclick="closePasswordModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let formData = null;

        function handleProfileUpdate(event) {
            event.preventDefault();
            formData = new FormData(event.target);
            openVerificationModal();
            return false;
        }
    </script>
    <script src="js/password-modal.js"></script>
    <script src="js/profile-handler.js"></script>
</body>

</html>
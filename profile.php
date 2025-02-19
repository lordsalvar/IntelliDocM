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
$user_query = "SELECT full_name, username, email, contact, profile_picture FROM users WHERE id = ?";
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
                    <div class="profile-picture-section">
                        <div class="profile-picture">
                            <img src="<?= !empty($user_data['profile_picture']) ? 'uploads/profile_pictures/' . $user_data['profile_picture'] : 'images/default.png' ?>"
                                alt="Profile Picture"
                                id="profileImage">
                            <div class="picture-overlay">
                                <label for="profilePictureInput" class="change-picture-btn">
                                    <i class="fas fa-camera"></i>
                                    <span>Change Picture</span>
                                </label>
                            </div>
                        </div>
                        <input type="file"
                            id="profilePictureInput"
                            accept="image/*"
                            style="display: none;"
                            onchange="handleProfilePictureChange(this)">
                    </div>
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
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="changePasswordForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
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
    <script>
        // Initialize the password change modal
        const passwordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));

        // Handle password change form submission
        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Validate passwords match
            if (newPassword !== confirmPassword) {
                showAlert('error', 'New passwords do not match!');
                return;
            }

            try {
                const response = await fetch('ajax/update_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        currentPassword,
                        newPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('success', 'Password updated successfully!');
                    this.reset(); // Reset form
                    passwordModal.hide(); // Close modal using Bootstrap modal instance
                } else {
                    showAlert('error', data.message || 'Failed to update password');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while updating password');
            }
        });

        // Function to show alerts
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.alert-container').appendChild(alertDiv);

            // Auto dismiss after 3 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Add this to properly handle modal closing
        document.querySelector('#changePasswordModal .btn-close').addEventListener('click', () => {
            passwordModal.hide();
        });

        document.querySelector('#changePasswordModal .btn-secondary').addEventListener('click', () => {
            passwordModal.hide();
        });

        // Reset form when modal is hidden
        document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('changePasswordForm').reset();
        });

        async function handleProfilePictureChange(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', input.files[0]);
                formData.append('action', 'update_picture');

                // Add current profile picture filename if exists
                const currentPicture = document.getElementById('profileImage').src.split('/').pop();
                if (currentPicture !== 'default.png') {
                    formData.append('old_picture', currentPicture);
                }

                try {
                    const response = await fetch('ajax/update_profile.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        document.getElementById('profileImage').src = result.image_url;
                        showAlert('success', 'Profile picture updated successfully!');
                    } else {
                        showAlert('error', result.message || 'Failed to update profile picture');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', 'An error occurred while updating profile picture');
                }
            }
        }
    </script>
    <style>
        .profile-picture-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-picture {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(139, 0, 0, 0.2);
            border: 3px solid #fff;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .picture-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(139, 0, 0, 0.8);
            padding: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .profile-picture:hover .picture-overlay {
            opacity: 1;
        }

        .change-picture-btn {
            color: white;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.9rem;
        }

        .change-picture-btn i {
            font-size: 1.2rem;
        }

        .alert-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }
    </style>
</body>

</html>
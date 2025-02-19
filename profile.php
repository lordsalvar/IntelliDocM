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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        <div class="content">
            <div class="profile-wrapper">
                <!-- Profile Header -->
                <div class="profile-header">
                    <h2>Profile Settings</h2>
                    <p>Manage your account information and preferences</p>
                </div>

                <!-- Main Profile Content -->
                <div class="profile-grid">
                    <!-- Left Column - Profile Picture -->
                    <div class="profile-sidebar">
                        <div class="profile-picture-card">
                            <div class="picture-container">
                                <img src="<?= !empty($user_data['profile_picture']) ? 'uploads/profile_pictures/' . $user_data['profile_picture'] : 'images/default.png' ?>"
                                    alt="Profile Picture"
                                    id="profileImage">
                                <div class="picture-overlay">
                                    <label for="profilePictureInput" class="upload-btn">
                                        <i class="fas fa-camera"></i>
                                        <span>Change Photo</span>
                                    </label>
                                </div>
                            </div>
                            <div class="picture-info">
                                <h3><?= htmlspecialchars($user_data['full_name']) ?></h3>
                                <span class="user-role"><?= ucfirst($_SESSION['role']) ?></span>
                            </div>
                            <input type="file"
                                id="profilePictureInput"
                                accept="image/*"
                                style="display: none;"
                                onchange="handleProfilePictureChange(this)">
                        </div>
                    </div>

                    <!-- Right Column - Profile Form -->
                    <div class="profile-main">
                        <div class="profile-card">
                            <div class="card-header">
                                <h3>Personal Information</h3>
                                <p>Update your personal details</p>
                            </div>
                            <form id="profileForm" class="profile-form" onsubmit="return handleProfileUpdate(event)">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="full_name">Full Name</label>
                                        <div class="input-group">
                                            <i class="fas fa-user"></i>
                                            <input type="text" id="full_name" name="full_name"
                                                value="<?= htmlspecialchars($user_data['full_name']) ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <div class="input-group">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" id="email" name="email"
                                                value="<?= htmlspecialchars($user_data['email']) ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <div class="input-group">
                                            <i class="fas fa-phone"></i>
                                            <input type="tel" id="phone" name="phone"
                                                value="<?= htmlspecialchars($user_data['contact']) ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <div class="input-group">
                                            <i class="fas fa-user-tag"></i>
                                            <input type="text" id="username"
                                                value="<?= htmlspecialchars($user_data['username']) ?>" readonly>
                                        </div>
                                        <small>Username cannot be changed</small>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="openPasswordModal()">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <!-- Crop Modal -->
    <div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cropContainer" style="height: 400px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="cropAndUpload">
                        <i class="fas fa-crop"></i> Save
                    </button>
                </div>
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

        let croppie = null;
        const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));

        async function handleProfilePictureChange(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file type
                if (!file.type.match('image.*')) {
                    showAlert('error', 'Please select an image file');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Destroy existing croppie instance if exists
                    if (croppie) {
                        croppie.destroy();
                    }

                    // Initialize new croppie instance
                    croppie = new Croppie(document.getElementById('cropContainer'), {
                        viewport: {
                            width: 300,
                            height: 300,
                            type: 'circle'
                        },
                        boundary: {
                            width: 400,
                            height: 400
                        },
                        enableOrientation: true,
                        enableZoom: true,
                        enableResize: false,
                        mouseWheelZoom: 'ctrl'
                    });

                    // Bind image
                    croppie.bind({
                        url: e.target.result
                    }).then(() => {
                        cropModal.show();
                    });
                };
                reader.readAsDataURL(file);
            }
        }

        // Handle crop and upload
        document.getElementById('cropAndUpload').addEventListener('click', async function() {
            if (!croppie) return;

            try {
                const blob = await croppie.result({
                    type: 'blob',
                    size: 'viewport',
                    format: 'jpeg',
                    quality: 0.9
                });

                const formData = new FormData();
                formData.append('profile_picture', blob, 'profile.jpg');
                formData.append('action', 'update_picture');

                // Get current profile picture filename
                const currentPicture = document.getElementById('profileImage').src.split('/').pop();
                if (currentPicture !== 'default.png') {
                    formData.append('old_picture', currentPicture);
                }

                const response = await fetch('ajax/update_profile.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Update profile picture with cache-busting query parameter
                    document.getElementById('profileImage').src = result.image_url + '?t=' + new Date().getTime();
                    showAlert('success', 'Profile picture updated successfully');
                    cropModal.hide();
                } else {
                    throw new Error(result.message || 'Failed to update profile picture');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', error.message || 'An error occurred while updating profile picture');
            }
        });

        // Clean up when modal is hidden
        document.getElementById('cropModal').addEventListener('hidden.bs.modal', function() {
            if (croppie) {
                croppie.destroy();
                croppie = null;
            }
            document.getElementById('profilePictureInput').value = '';
        });

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            // Add alert to the page
            const alertContainer = document.querySelector('.alert-container') || createAlertContainer();
            alertContainer.appendChild(alertDiv);

            // Auto dismiss after 3 seconds
            setTimeout(() => alertDiv.remove(), 3000);
        }

        function createAlertContainer() {
            const container = document.createElement('div');
            container.className = 'alert-container';
            document.body.appendChild(container);
            return container;
        }
    </script>
    <style>
        .profile-wrapper {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .profile-header {
            margin-bottom: 2rem;
        }

        .profile-header h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        /* Profile Sidebar Styles */
        .profile-picture-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .picture-container {
            position: relative;
            width: 100%;
            padding-top: 100%;
        }

        .picture-container img {
            position: absolute;
            top: 0;
            left: 0;
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
            padding: 1rem;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .picture-container:hover .picture-overlay {
            opacity: 1;
        }

        .upload-btn {
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .picture-info {
            padding: 1.5rem;
            text-align: center;
        }

        .picture-info h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }

        .user-role {
            color: #666;
            font-size: 0.9rem;
            display: block;
            margin-top: 0.5rem;
        }

        /* Profile Main Content Styles */
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .card-header h3 {
            margin: 0;
            color: #2c3e50;
        }

        .card-header p {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0 0 0;
        }

        .profile-form {
            padding: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            color: #8B0000;
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
            outline: none;
        }

        .input-group input:read-only {
            background: #f8f9fa;
            cursor: not-allowed;
        }

        .form-actions {
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: #8B0000;
            color: white;
        }

        .btn-primary:hover {
            background: #a00000;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2c3e50;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .modal-lg {
            max-width: 600px;
        }

        #cropContainer {
            height: 400px;
        }

        .cr-boundary {
            border-radius: 8px;
            overflow: hidden;
        }

        .cr-slider-wrap {
            margin: 1rem auto;
            width: 80% !important;
        }

        .cr-slider {
            width: 100% !important;
        }

        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</body>

</html>
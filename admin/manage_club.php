<?php
session_start();
require_once '../database.php';
require_once 'includes/club_functions.php';

// Validate admin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    switch ($_POST['action'] ?? '') {
        case 'fetchClubs':
            $response = fetchAllClubs();
            break;

        case 'addClub':
            $success = addClub(
                $_POST['clubName'],
                $_POST['acronym'],
                $_POST['type'],
                $_POST['moderator']
            );
            $response = ['success' => $success];
            break;

        case 'updateClub':
            $success = updateClub(
                $_POST['clubId'],
                $_POST['clubName'],
                $_POST['acronym'],
                $_POST['type'],
                $_POST['moderator']
            );
            $response = ['success' => $success];
            break;

        case 'deleteClub':
            $success = deleteClub($_POST['clubId']);
            $response = ['success' => $success];
            break;

        case 'getMembers':
            $members = getClubMembers($_POST['clubId']);
            $response = ['members' => []];
            while ($member = $members->fetch_assoc()) {
                $response['members'][] = $member;
            }
            break;
    }

    echo json_encode($response);
    exit;
}

// Regular page load
$clubs = fetchAllClubs();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Management - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/manage-club.css">

</head>

<body>
    <div class="dashboard">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="content">
            <!-- Alert Container -->
            <div class="alert-container position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users-gear"></i>
                    <div class="stat-info">
                        <h3>Total Clubs</h3>
                        <span class="stat-number">
                            <?= number_format($conn->query("SELECT COUNT(*) FROM clubs")->fetch_row()[0]) ?>
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-group"></i>
                    <div class="stat-info">
                        <h3>Total Members</h3>
                        <span class="stat-number">
                            <?= number_format($conn->query("SELECT COUNT(*) FROM club_memberships")->fetch_row()[0]) ?>
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-tie"></i>
                    <div class="stat-info">
                        <h3>Club Moderators</h3>
                        <span class="stat-number">
                            <?= number_format($conn->query("SELECT COUNT(DISTINCT moderator) FROM clubs")->fetch_row()[0]) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-card">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Club Management</h2>
                    <button class="btn-action add-btn" data-bs-toggle="modal" data-bs-target="#addClubModal">
                        <i class="fas fa-plus"></i> Add New Club
                    </button>
                </div>

                <!-- Search and Filters -->
                <div class="search-controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="clubSearch" placeholder="Search clubs...">
                    </div>
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-th"></i> All
                        </button>
                        <button class="filter-btn" data-filter="academic">
                            <i class="fas fa-graduation-cap"></i> Academic
                        </button>
                        <button class="filter-btn" data-filter="non-academic">
                            <i class="fas fa-users"></i> Non-Academic
                        </button>
                        <button class="filter-btn" data-filter="acco">ACCO</button>
                        <button class="filter-btn" data-filter="csg">CSG</button>
                    </div>
                </div>

                <!-- Club Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Club Name</th>
                                <th>Type</th>
                                <th>Members</th>
                                <th>Moderator</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clubTableBody">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include your existing modals here -->
    <!--ADD USER MODAL -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addUserForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" class="form-control" id="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" class="form-control" id="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="form-group">
                            <label for="club_id">Select Club:</label>
                            <select class="form-control" id="club_id" required></select>
                        </div>
                        <hr>
                        <div>
                            <div>
                                <label for="laber">Note:</label>
                                <p class="">Set Dean/Moderator Designation for admin access</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="designation">Designation:</label>
                            <input type="text" class="form-control" id="designation" required>
                        </div>

                        <div class="form-group">
                            <label for="contact">Contact Number:</label>
                            <input type="text" class="form-control" id="contact" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editUsername">Username:</label>
                            <input type="text" class="form-control" id="editUsername" required>
                        </div>
                        <div class="form-group">
                            <label for="editEmail">Email:</label>
                            <input type="email" class="form-control" id="editEmail" required>
                        </div>
                        <div class="form-group">
                            <label for="editPassword">New Password:</label>
                            <input type="password" class="form-control" id="editPassword" required>
                        </div>
                        <div class="form-group">
                            <label for="editDesignation">Designation:</label>
                            <input type="text" class="form-control" id="editDesignation" required>
                        </div>
                        <div class="form-group">
                            <label for="editContact">Contact:</label>
                            <input type="text" class="form-control" id="editContact" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="responseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Response</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include your existing JavaScript with updates -->
    <script>
        // ... existing JavaScript code ...

        // Add new theme-specific functions
        function initializeFilters() {
            $('.filter-btn').click(function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                filterClubs($(this).data('filter'));
            });
        }

        function filterClubs(type) {
            const rows = $('#clubTableBody tr');
            if (type === 'all') {
                rows.show();
            } else {
                rows.hide();
                rows.filter(`[data-type="${type}"]`).show();
            }
        }

        // Initialize components
        $(document).ready(function() {
            fetchClubs();
            initializeFilters();

            // Add search functionality
            $('#clubSearch').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                $('#clubTableBody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(searchText) > -1);
                });
            });

            // Initialize club management
            function refreshClubTable() {
                $.post('manage_club.php', {
                    action: 'fetchClubs'
                }, function(response) {
                    const clubs = JSON.parse(response);
                    displayClubs(clubs);
                });
            }

            function displayClubs(clubs) {
                const tbody = $('#clubTableBody');
                tbody.empty();

                clubs.forEach(club => {
                    tbody.append(`
                        <tr data-type="${club.club_type.toLowerCase()}">
                            <td>${club.club_name}</td>
                            <td>${club.club_type}</td>
                            <td>
                                <span class="member-badge">
                                    <i class="fas fa-users"></i> ${club.member_count}
                                </span>
                            </td>
                            <td>${club.moderator}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick="editClub(${club.club_id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteClub(${club.club_id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                });
            }

            // Initialize page
            refreshClubTable();
        });
    </script>
</body>

</html>
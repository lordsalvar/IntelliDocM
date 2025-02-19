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
                        <h3>Total Orgnazation</h3>
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
                    <h2><i class="fas fa-users"></i> Organization Management</h2>
                    <button class="btn-action add-btn" data-bs-toggle="modal" data-bs-target="#addClubModal">
                        <i class="fas fa-plus"></i> Add New Club
                    </button>
                </div>

                <!-- Search and Filters -->
                <div class="search-controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="clubSearch" placeholder="Search organizations...">
                        <button type="button" class="clear-search" id="clearSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-type="all">
                            <i class="fas fa-th-large"></i> All
                        </button>
                        <button class="filter-btn" data-type="Academic">
                            <i class="fas fa-graduation-cap"></i> Academic
                        </button>
                        <button class="filter-btn" data-type="Non-Academic">
                            <i class="fas fa-users"></i> Non-Academic
                        </button>
                        <button class="filter-btn" data-type="ACCO">
                            <i class="fas fa-building"></i> ACCO
                        </button>
                        <button class="filter-btn" data-type="CSG">
                            <i class="fas fa-user-friends"></i> CSG
                        </button>
                        <button class="filter-btn" data-type="College-LGU">
                            <i class="fas fa-university"></i> College-LGU
                        </button>
                    </div>
                </div>

                <!-- Clubs Grid Layout -->
                <div class="clubs-grid" id="clubsGrid">
                    <?php if (empty($clubs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <h3>No Clubs Found</h3>
                            <p>There are no clubs registered in the system yet. Use the "Add New Club" button above to create one.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($clubs as $club): ?>
                            <div class="club-card" data-type="<?= strtolower($club['club_type']) ?>">
                                <div class="club-header">
                                    <img src="<?= htmlspecialchars($club['club_logo'] ?? '../images/clubs/default_club.png') ?>"
                                        alt="<?= htmlspecialchars($club['club_name']) ?>"
                                        class="club-logo"
                                        onerror="this.src='../images/clubs/default_club.png';">
                                    <div class="club-badges">
                                        <span class="type-badge <?= strtolower($club['club_type']) ?>">
                                            <?= htmlspecialchars($club['club_type']) ?>
                                        </span>
                                        <span class="member-count">
                                            <i class="fas fa-users"></i> <?= $club['member_count'] ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="club-body">
                                    <h3 class="club-name"><?= htmlspecialchars($club['club_name']) ?></h3>
                                    <p class="club-acronym"><?= htmlspecialchars($club['acronym']) ?></p>
                                    <p class="club-moderator">
                                        <i class="fas fa-user-tie"></i> <?= htmlspecialchars($club['moderator']) ?>
                                    </p>
                                </div>
                                <div class="club-actions">
                                    <button class="btn-icon" onclick="viewClubDetails(<?= $club['club_id'] ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon" onclick="editClub(<?= $club['club_id'] ?>)" title="Edit Club">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon" onclick="manageMembers(<?= $club['club_id'] ?>)" title="Manage Members">
                                        <i class="fas fa-users-cog"></i>
                                    </button>
                                    <button class="btn-icon delete" onclick="deleteClub(<?= $club['club_id'] ?>)" title="Delete Club">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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

    <!-- Add this modal before the closing body tag -->
    <div class="modal fade" id="addClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Organization</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addClubForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Organization Name</label>
                            <input type="text" class="form-control" name="clubName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acronym</label>
                            <input type="text" class="form-control" name="acronym" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-control" name="type" required>
                                <option value="Academic">Academic</option>
                                <option value="Non-Academic">Non-Academic</option>
                                <option value="ACCO">ACCO</option>
                                <option value="CSG">CSG</option>
                                <option value="College-LGU">College-LGU</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Moderator</label>
                            <input type="text" class="form-control" name="moderator" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Organization Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            <small class="text-muted">Optional. Maximum size: 2MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Organization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add View Club Details Modal -->
    <div class="modal fade" id="viewClubModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Organization Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="club-detail-header">
                        <img src="" alt="Club Logo" id="viewClubLogo" class="club-detail-logo">
                        <div class="club-detail-title">
                            <h3 id="viewClubName"></h3>
                            <span class="badge" id="viewClubType"></span>
                        </div>
                    </div>
                    <div class="club-detail-info">
                        <div class="info-group">
                            <label><i class="fas fa-font"></i> Acronym</label>
                            <p id="viewClubAcronym"></p>
                        </div>
                        <div class="info-group">
                            <label><i class="fas fa-user-tie"></i> Moderator</label>
                            <p id="viewClubModerator"></p>
                        </div>
                        <div class="info-group">
                            <label><i class="fas fa-users"></i> Total Members</label>
                            <p id="viewClubMembers"></p>
                        </div>
                    </div>
                    <div class="member-list-section">
                        <h4>Members List</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Designation</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody id="membersList">
                                    <!-- Members will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Club Modal -->
    <div class="modal fade" id="editClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Organization</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editClubForm" enctype="multipart/form-data">
                    <input type="hidden" name="clubId" id="editClubId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Organization Name</label>
                            <input type="text" class="form-control" name="clubName" id="editClubName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Acronym</label>
                            <input type="text" class="form-control" name="acronym" id="editClubAcronym" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-control" name="type" id="editClubType" required>
                                <option value="Academic">Academic</option>
                                <option value="Non-Academic">Non-Academic</option>
                                <option value="ACCO">ACCO</option>
                                <option value="CSG">CSG</option>
                                <option value="College-LGU">College-LGU</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Moderator</label>
                            <input type="text" class="form-control" name="moderator" id="editClubModerator" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Organization Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            <small class="text-muted">Leave empty to keep current logo</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Member Management Modal -->
    <div class="modal fade" id="manageMembersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Club Members</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Add Member Form -->
                    <form id="addMemberForm" class="mb-4">
                        <input type="hidden" id="memberClubId" name="clubId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="fullName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact</label>
                                <input type="text" class="form-control" name="contact" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" name="designation" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Member</button>
                    </form>

                    <!-- Members List -->
                    <h6 class="mb-3">Current Members</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Designation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="membersTableBody">
                                <!-- Members will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this new modal after your existing modals -->
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editMemberForm">
                    <div class="modal-body">
                        <input type="hidden" name="userId" id="editMemberId">
                        <input type="hidden" name="clubId" id="editMemberClubId">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="fullName" id="editMemberName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editMemberEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" class="form-control" name="contact" id="editMemberContact" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" name="designation" id="editMemberDesignation" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
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
                $('.club-card').each(function() {
                    const clubName = $(this).find('.club-name').text().toLowerCase();
                    const clubAcronym = $(this).find('.club-acronym').text().toLowerCase();
                    const clubType = $(this).find('.type-badge').text().toLowerCase();
                    const clubModerator = $(this).find('.club-moderator').text().toLowerCase();

                    const matches = clubName.includes(searchText) ||
                        clubAcronym.includes(searchText) ||
                        clubType.includes(searchText) ||
                        clubModerator.includes(searchText);

                    $(this).toggle(matches);
                });

                // Show/hide empty state message
                const visibleCards = $('.club-card:visible').length;
                if (visibleCards === 0) {
                    if (!$('.empty-search-state').length) {
                        $('#clubsGrid').append(`
                            <div class="empty-search-state empty-state">
                                <i class="fas fa-search"></i>
                                <h3>No Results Found</h3>
                                <p>No organizations match your search criteria</p>
                            </div>
                        `);
                    }
                } else {
                    $('.empty-search-state').remove();
                }
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

        // Add Club Form Handler
        $('#addClubForm').on('submit', function(e) {
            e.preventDefault();

            // Create FormData object
            const formData = new FormData(this);
            formData.append('action', 'addClub');

            $.ajax({
                url: 'ajax/handle_club.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Show success message
                            alert('Organization added successfully!');
                            // Reset form
                            $('#addClubForm')[0].reset();
                            // Close modal
                            $('#addClubModal').modal('hide');
                            // Refresh the page to show new club
                            window.location.reload();
                        } else {
                            alert('Error: ' + (result.message || 'Failed to add organization'));
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Error: Failed to process server response');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', error);
                    alert('Error: Failed to send request');
                }
            });
        });

        // Replace the existing search and filter functions with these:
        $(document).ready(function() {
            // Search functionality
            $('#clubSearch').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                filterClubs(searchText, $('.filter-btn.active').data('type'));
            });

            // Filter buttons
            $('.filter-buttons .filter-btn').click(function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                const type = $(this).data('type');
                const searchText = $('#clubSearch').val().toLowerCase();
                filterClubs(searchText, type);
            });

            function filterClubs(searchText, type) {
                $('.club-card').each(function() {
                    const clubName = $(this).find('.club-name').text().toLowerCase();
                    const clubAcronym = $(this).find('.club-acronym').text().toLowerCase();
                    const clubType = $(this).find('.type-badge').text().toLowerCase();
                    const clubModerator = $(this).find('.club-moderator').text().toLowerCase();

                    const matchesSearch = !searchText ||
                        clubName.includes(searchText) ||
                        clubAcronym.includes(searchText) ||
                        clubType.includes(searchText) ||
                        clubModerator.includes(searchText);

                    const matchesType = type === 'all' || clubType === type.toLowerCase();

                    $(this).toggle(matchesSearch && matchesType);
                });

                // Show/hide empty state
                const visibleCards = $('.club-card:visible').length;
                $('.empty-search-state').remove();

                if (visibleCards === 0) {
                    $('#clubsGrid').append(`
                        <div class="empty-search-state empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No Results Found</h3>
                            <p>No organizations match your search criteria</p>
                        </div>
                    `);
                }
            }
        });

        // Filter and search functionality
        function filterAndSearchClubs() {
            const searchText = $('#clubSearch').val().toLowerCase();
            const activeType = $('.filter-btn.active').data('type').toLowerCase();

            $('.club-card').each(function() {
                const card = $(this);
                const cardType = card.data('type').toLowerCase();
                const content = card.find('.club-name, .club-acronym, .club-moderator').text().toLowerCase();

                const matchesSearch = !searchText || content.includes(searchText);
                const matchesType = activeType === 'all' || cardType === activeType;

                card.toggle(matchesSearch && matchesType);
            });

            // Update empty state
            const visibleCards = $('.club-card:visible').length;
            $('.empty-search-state').remove();

            if (visibleCards === 0) {
                $('#clubsGrid').append(`
                    <div class="empty-search-state empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No Results Found</h3>
                        <p>No organizations match your criteria</p>
                    </div>
                `);
            }
        }

        // Search input handler
        $('#clubSearch').on('input', filterAndSearchClubs);

        // Filter button handler
        $('.filter-btn').click(function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            filterAndSearchClubs();
        });

        // Clear search button handler
        $('#clearSearch').click(function() {
            $('#clubSearch').val('').focus();
            $(this).hide();
            filterAndSearchClubs();
        });

        // Show/hide clear button
        $('#clubSearch').on('input', function() {
            $('#clearSearch').toggle($(this).val().length > 0);
        });

        // ... rest of existing JavaScript code ...
    </script>

    <script>
        // Replace the existing filter function with this updated version
        $(document).ready(function() {
            // Filter and search functionality
            function filterAndSearchClubs() {
                const searchText = $('#clubSearch').val().toLowerCase();
                const activeType = $('.filter-btn.active').data('type');

                // Debug log
                console.log('Active filter type:', activeType);

                $('.club-card').each(function() {
                    const card = $(this);
                    const cardType = $(this).find('.type-badge').text().trim(); // Get exact text from badge
                    const content = card.find('.club-name, .club-acronym, .club-moderator').text().toLowerCase();

                    // Debug log
                    console.log('Card type:', cardType, 'Active type:', activeType);

                    const matchesSearch = !searchText || content.toLowerCase().includes(searchText);
                    const matchesType = activeType === 'all' || cardType === activeType;

                    // Debug log
                    console.log('Matches type:', matchesType, 'Matches search:', matchesSearch);

                    card.toggle(matchesSearch && matchesType);
                });

                // Show/hide empty state
                const visibleCards = $('.club-card:visible').length;
                $('.empty-search-state').remove();

                if (visibleCards === 0) {
                    $('#clubsGrid').append(`
                <div class="empty-search-state empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Results Found</h3>
                    <p>No organizations match your criteria</p>
                </div>
            `);
                }
            }

            // Search input handler
            $('#clubSearch').on('input', filterAndSearchClubs);

            // Filter button handler
            $('.filter-buttons .filter-btn').click(function(e) {
                e.preventDefault();
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                filterAndSearchClubs();
            });

            // Clear search button handler
            $('#clearSearch').click(function() {
                $('#clubSearch').val('');
                $(this).hide();
                filterAndSearchClubs();
            });

            // Initialize the filter
            filterAndSearchClubs();
        });

        // Add the view club details function
        function viewClubDetails(clubId) {
            // Show loading state
            $('#viewClubModal').modal('show');
            $('#membersList').html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');

            // Fetch club details
            $.ajax({
                url: 'ajax/get_club_details.php',
                type: 'POST',
                data: {
                    clubId: clubId
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Update modal with club details
                            $('#viewClubName').text(data.club.club_name);
                            $('#viewClubAcronym').text(data.club.acronym);
                            $('#viewClubType').text(data.club.club_type)
                                .removeClass()
                                .addClass('badge type-badge ' + data.club.club_type.toLowerCase());
                            $('#viewClubModerator').text(data.club.moderator);
                            $('#viewClubMembers').text(data.members.length);
                            $('#viewClubLogo').attr('src', data.club.club_logo || '../images/clubs/default_club.png');

                            // Populate members table
                            let membersHtml = '';
                            data.members.forEach(member => {
                                membersHtml += `
                                    <tr>
                                        <td>${member.full_name}</td>
                                        <td>${member.designation}</td>
                                        <td>${member.contact || 'N/A'}</td>
                                    </tr>
                                `;
                            });
                            $('#membersList').html(membersHtml || '<tr><td colspan="3" class="text-center">No members found</td></tr>');
                        } else {
                            throw new Error(data.message || 'Failed to load club details');
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        alert('Error loading club details');
                    }
                },
                error: function() {
                    alert('Error loading club details');
                    $('#viewClubModal').modal('hide');
                }
            });
        }

        // Prevent action buttons from triggering card click
        $(document).ready(function() {
            $('.club-actions button').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });

            // Make card content clickable
            $('.card-content').on('click', function(e) {
                if (!$(e.target).closest('.club-actions').length) {
                    const clubId = $(this).closest('.club-card').data('club-id');
                    viewClubDetails(clubId);
                }
            });
        });

        function editClub(clubId) {
            // Show loading state
            $('#editClubModal').modal('show');

            // Fetch club details
            $.ajax({
                url: 'ajax/get_club_details.php',
                type: 'POST',
                data: {
                    clubId: clubId
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Populate form with club details
                            $('#editClubId').val(clubId);
                            $('#editClubName').val(data.club.club_name);
                            $('#editClubAcronym').val(data.club.acronym);
                            $('#editClubType').val(data.club.club_type);
                            $('#editClubModerator').val(data.club.moderator);
                        } else {
                            throw new Error(data.message || 'Failed to load club details');
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        alert('Error loading club details');
                        $('#editClubModal').modal('hide');
                    }
                },
                error: function() {
                    alert('Error loading club details');
                    $('#editClubModal').modal('hide');
                }
            });
        }

        // Handle edit form submission
        $('#editClubForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'updateClub');

            $.ajax({
                url: 'ajax/handle_club.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Organization updated successfully!');
                            $('#editClubModal').modal('hide');
                            window.location.reload();
                        } else {
                            alert('Error: ' + (result.message || 'Failed to update organization'));
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        alert('Error: Failed to process server response');
                    }
                },
                error: function() {
                    alert('Error: Failed to send request');
                }
            });
        });

        function manageMembers(clubId) {
            $('#memberClubId').val(clubId); // Set the club ID for the add member form
            $('#manageMembersModal').modal('show');
            loadClubMembers(clubId);
        }

        function loadClubMembers(clubId) {
            $.ajax({
                url: 'ajax/manage_members.php',
                type: 'POST',
                data: {
                    action: 'getMembers',
                    clubId: clubId
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            let html = '';
                            result.members.forEach(member => {
                                html += `
                                    <tr>
                                        <td>${member.full_name}</td>
                                        <td>${member.email}</td>
                                        <td>${member.contact}</td>
                                        <td>${member.designation}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editMember(${member.id}, ${clubId}, ${JSON.stringify(member).replace(/"/g, '&quot;')})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="removeMember(${member.id}, ${clubId})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                            $('#membersTableBody').html(html || '<tr><td colspan="5" class="text-center">No members found</td></tr>');
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        alert('Error loading members');
                    }
                }
            });
        }

        function editMember(userId, clubId, memberData) {
            // Populate the edit form
            $('#editMemberId').val(userId);
            $('#editMemberClubId').val(clubId);
            $('#editMemberName').val(memberData.full_name);
            $('#editMemberEmail').val(memberData.email);
            $('#editMemberContact').val(memberData.contact);
            $('#editMemberDesignation').val(memberData.designation);

            // Show the modal
            $('#editMemberModal').modal('show');
        }

        // Handle edit member form submission
        $('#editMemberForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'updateMember');

            $.ajax({
                url: 'ajax/manage_members.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Member updated successfully!');
                            $('#editMemberModal').modal('hide');
                            loadClubMembers($('#editMemberClubId').val());
                        } else {
                            alert('Error: ' + (result.message || 'Failed to update member'));
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        alert('Error processing response');
                    }
                }
            });
        });

        function removeMember(userId, clubId) {
            if (confirm('Are you sure you want to remove this member?')) {
                $.ajax({
                    url: 'ajax/manage_members.php',
                    type: 'POST',
                    data: {
                        action: 'removeMember',
                        userId: userId,
                        clubId: clubId
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                loadClubMembers(clubId);
                            } else {
                                alert('Error: ' + (result.message || 'Failed to remove member'));
                            }
                        } catch (e) {
                            console.error('Error:', e);
                            alert('Error processing response');
                        }
                    }
                });
            }
        }

        // Add this in your JavaScript section
        function deleteClub(clubId) {
            if (confirm('Are you sure you want to delete this organization? This action cannot be undone.')) {
                $.ajax({
                    url: 'ajax/handle_club.php',
                    type: 'POST',
                    data: {
                        action: 'deleteClub',
                        clubId: clubId
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('Organization deleted successfully!');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (result.message || 'Failed to delete organization'));
                            }
                        } catch (e) {
                            console.error('Error:', e);
                            alert('Error processing response');
                        }
                    },
                    error: function() {
                        alert('Error: Failed to send request');
                    }
                });
            }
        }

        // Add this handler for the add member form
        $('#addMemberForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'addMember');

            $.ajax({
                url: 'ajax/manage_members.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Member added successfully!');
                            $('#addMemberForm')[0].reset();
                            loadClubMembers($('#memberClubId').val());
                        } else {
                            alert('Error: ' + (result.message || 'Failed to add member'));
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        alert('Error processing response');
                    }
                },
                error: function() {
                    alert('Error: Failed to send request');
                }
            });
        });

        // Update the manageMembers function
        function manageMembers(clubId) {
            $('#memberClubId').val(clubId); // Set the club ID for the add member form
            $('#manageMembersModal').modal('show');
            loadClubMembers(clubId);
        }

        // ... rest of existing code ...
    </script>
</body>

</html>

<style>
    .clubs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .club-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: default;
        /* Remove pointer cursor */
        position: relative;
    }

    .club-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .card-content {
        cursor: default;
        /* Remove pointer cursor */
    }

    .club-header {
        position: relative;
        padding: 1.5rem;
        background: linear-gradient(45deg, #f8f9fa, white);
        text-align: center;
    }

    .club-logo {
        width: 120px;
        height: 120px;
        border-radius: 60px;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .club-badges {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* Update and expand type badge styles */
    .type-badge.academic {
        background: #e3f2fd;
        color: #1976d2;
    }

    .type-badge.non-academic {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .type-badge.acco {
        background: #fff3e0;
        color: #e65100;
    }

    .type-badge.csg {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .type-badge.college-lgu {
        background: #efebe9;
        color: #4e342e;
    }

    /* Add hover effects for the badges */
    .type-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .member-count {
        background: #fff3e0;
        color: #f57c00;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
    }

    .club-body {
        padding: 1.5rem;
        text-align: center;
    }

    .club-name {
        margin: 0;
        font-size: 1.25rem;
        color: #333;
        font-weight: 600;
    }

    .club-acronym {
        color: #666;
        font-size: 1rem;
        margin: 0.5rem 0;
    }

    .club-moderator {
        color: #666;
        font-size: 0.9rem;
        margin: 0.5rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .club-actions {
        padding: 1rem;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: center;
        gap: 1rem;
        position: relative;
        z-index: 10;
        background: white;
    }

    .btn-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        background: #f8f9fa;
        color: #666;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
        pointer-events: auto;
    }

    .btn-icon:hover {
        background: #e9ecef;
        color: #333;
    }

    .btn-icon.delete:hover {
        background: #dc3545;
        color: white;
    }

    .empty-state {
        grid-column: 1 / -1;
        background: white;
        border-radius: 15px;
        padding: 3rem;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .empty-state i {
        font-size: 4rem;
        color: #ccc;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        color: #333;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: #666;
        margin-bottom: 1.5rem;
    }

    .empty-state .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        background: var(--primary-color, #8B0000);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }

    .empty-state .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 0, 0, 0.2);
    }

    /* Update the button styles */
    .btn-action.add-btn {
        background: #007bff;
        /* Change to blue */
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-action.add-btn:hover {
        background: #0056b3;
        /* Darker blue on hover */
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }

    /* Update modal submit button */
    .modal-footer .btn-primary {
        background: #007bff;
        border-color: #007bff;
    }

    .modal-footer .btn-primary:hover {
        background: #0056b3;
        border-color: #0056b3;
    }

    .empty-search-state {
        display: none;
        grid-column: 1 / -1;
    }

    .empty-search-state.show {
        display: block;
    }

    .search-controls {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .search-box {
        position: relative;
        margin-bottom: 1rem;
    }

    .search-box input {
        width: 100%;
        padding: 0.8rem 1rem 0.8rem 2.5rem;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .search-box input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        outline: none;
    }

    .search-box i.fa-search {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .clear-search {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        padding: 0.25rem;
        display: none;
    }

    .clear-search:hover {
        color: #dc3545;
    }

    .filter-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #e0e0e0;
        background: white;
        border-radius: 6px;
        color: #666;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-btn:hover {
        background: #f8f9fa;
        border-color: #007bff;
        color: #007bff;
    }

    .filter-btn.active {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }

    @media (max-width: 768px) {
        .filter-buttons {
            grid-template-columns: repeat(2, 1fr);
        }

        .filter-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Update search control styles */
    .search-controls {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }

    .filter-buttons {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
        border: 1px solid #e0e0e0;
        background: white;
        border-radius: 4px;
        color: #666;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .filter-btn i {
        font-size: 0.8rem;
    }

    .search-box input {
        padding: 0.6rem 1rem 0.6rem 2.2rem;
        font-size: 0.95rem;
    }

    /* ... rest of existing styles ... */

    .club-detail-header {
        display: flex;
        align-items: center;
        gap: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
        margin-bottom: 1.5rem;
    }

    .club-detail-logo {
        width: 120px;
        height: 120px;
        border-radius: 60px;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .club-detail-title h3 {
        margin: 0;
        color: #333;
        font-size: 1.5rem;
    }

    .club-detail-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .info-group {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }

    .info-group label {
        color: #666;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .info-group p {
        margin: 0;
        color: #333;
        font-weight: 500;
    }

    .member-list-section {
        background: #fff;
        border-radius: 8px;
        padding: 1rem;
    }

    .member-list-section h4 {
        margin-bottom: 1rem;
        color: #333;
    }

    .club-card::after {
        display: none;
    }
</style>
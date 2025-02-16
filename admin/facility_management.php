<?php
session_start();
require_once '../database.php';
require_once '../includes/facility_functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stats = [
    'total_facilities' => $conn->query("SELECT COUNT(*) FROM facilities")->fetch_row()[0],
    'total_rooms' => $conn->query("SELECT COUNT(*) FROM rooms")->fetch_row()[0],
    'total_bookings' => $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0],
    'pending_bookings' => $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetch_row()[0]
];

// Fetch facilities
$sql = "SELECT f.*, 
        (SELECT COUNT(*) FROM rooms WHERE facility_id = f.id) as room_count,
        (SELECT COUNT(*) FROM bookings WHERE facility_id = f.id) as booking_count
        FROM facilities f 
        ORDER BY f.name ASC";
$facilities = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Management - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/facility.css">
</head>

<body>
    <div class="dashboard">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="content">
            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <div class="stat-info">
                        <h3>Total Facilities</h3>
                        <span class="stat-number"><?= $stats['total_facilities'] ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-door-open"></i>
                    <div class="stat-info">
                        <h3>Total Rooms</h3>
                        <span class="stat-number"><?= $stats['total_rooms'] ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <div class="stat-info">
                        <h3>Total Bookings</h3>
                        <span class="stat-number"><?= $stats['total_bookings'] ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-info">
                        <h3>Pending Bookings</h3>
                        <span class="stat-number"><?= $stats['pending_bookings'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-card">
                <div class="card-header">
                    <h2><i class="fas fa-building"></i> Facility Management</h2>
                    <div class="header-actions">
                        <button class="btn-action" onclick="FacilityManager.showBookingHistory()">
                            <i class="fas fa-history"></i> Booking History
                        </button>
                        <button class="btn-action add-btn" data-bs-toggle="modal" data-bs-target="#addFacilityModal">
                            <i class="fas fa-plus"></i> Add Facility
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Search and Filters -->
                    <div class="controls">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="facilitySearch" placeholder="Search facilities...">
                        </div>
                        <div class="filter-buttons">
                            <button class="filter-btn active" data-filter="all">All</button>
                            <button class="filter-btn" data-filter="available">Available</button>
                            <button class="filter-btn" data-filter="booked">Booked</button>
                        </div>
                    </div>

                    <!-- Facilities Grid -->
                    <div class="facilities-grid">
                        <?php while ($facility = $facilities->fetch_assoc()): ?>
                            <div class="facility-card">
                                <div class="facility-header">
                                    <h3><?= htmlspecialchars($facility['name']) ?></h3>
                                    <span class="facility-code"><?= htmlspecialchars($facility['code']) ?></span>
                                </div>
                                <p class="facility-description"><?= htmlspecialchars($facility['description']) ?></p>
                                <div class="facility-stats">
                                    <div class="stat">
                                        <i class="fas fa-door-open"></i>
                                        <span><?= $facility['room_count'] ?> Rooms</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-calendar-check"></i>
                                        <span><?= $facility['booking_count'] ?> Bookings</span>
                                    </div>
                                </div>
                                <div class="facility-actions">
                                    <button class="btn-icon" onclick="FacilityManager.manageFacility(<?= $facility['id'] ?>)" title="Manage Facility">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <button class="btn-icon" onclick="FacilityManager.viewRooms(<?= $facility['id'] ?>)" title="View Rooms">
                                        <i class="fas fa-door-open"></i>
                                    </button>
                                    <button class="btn-icon" onclick="FacilityManager.viewBookings(<?= $facility['id'] ?>)" title="View Bookings">
                                        <i class="fas fa-calendar"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    <?php include '../includes/facility_modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/facility-management.js"></script>
</body>

</html>
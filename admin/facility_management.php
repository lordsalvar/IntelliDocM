<?php
session_start();
require_once '../database.php';

// Validate admin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ...existing facility management logic...
}

// Fetch facilities with pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$searchParam = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchSQL = '';
if ($searchParam !== '') {
    $searchSQL = "WHERE (name LIKE ? OR code LIKE ?)";
}

// Get total count
$countSql = "SELECT COUNT(*) AS total FROM facilities " . $searchSQL;
$params = [];
if ($searchParam !== '') {
    $searchPattern = "%$searchParam%";
    $params = [$searchPattern, $searchPattern];
}

$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Fetch facilities
$sql = "SELECT * FROM facilities $searchSQL ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)) . 'ii', ...array_merge($params, [$start, $limit]));
} else {
    $stmt->bind_param('ii', $start, $limit);
}
$stmt->execute();
$facilities = $stmt->get_result();
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
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .facilities-container {
            padding: 2rem;
        }

        .facility-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(183, 28, 28, 0.1);
            border-left: 5px solid #B71C1C;
            transition: transform 0.3s ease;
        }

        .facility-card:hover {
            transform: translateY(-5px);
        }

        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(183, 28, 28, 0.1);
            border-left: 5px solid #B71C1C;
        }

        .controls-header h2 {
            color: #B71C1C;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .search-container {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(183, 28, 28, 0.2);
            width: 300px;
            padding-left: 2.5rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #B71C1C;
        }

        .action-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .add-btn {
            background: #B71C1C;
            color: white;
        }

        .add-btn:hover {
            background: #D32F2F;
            transform: translateY(-2px);
        }

        .facility-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(183, 28, 28, 0.1);
        }

        .facility-title h3 {
            margin: 0;
            color: #B71C1C;
            font-size: 1.3rem;
        }

        .facility-code {
            background: #ffebee;
            color: #B71C1C;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .room-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .room-card:hover {
            background: #fff;
            box-shadow: 0 4px 12px rgba(183, 28, 28, 0.1);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: #B71C1C;
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-footer {
            border-top: none;
        }

        .pagination {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }

        .pagination .page-link {
            color: #B71C1C;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .pagination .active .page-link {
            background: #B71C1C;
            color: white;
        }

        .rooms-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(183, 28, 28, 0.1);
        }

        .rooms-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .rooms-header h4 {
            color: #B71C1C;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-room-btn {
            background: #28a745;
            color: white;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .room-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .room-info h5 {
            margin: 0;
            color: #333;
        }

        .capacity {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .room-actions {
            display: flex;
            gap: 0.5rem;
        }

        .edit-room-btn,
        .delete-room-btn {
            padding: 0.4rem;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .edit-room-btn {
            background: #1976D2;
            color: white;
        }

        .delete-room-btn {
            background: #dc3545;
            color: white;
        }

        .empty-rooms {
            text-align: center;
            padding: 1rem;
            color: #666;
            font-style: italic;
        }

        /* Quick Access Panel */
        .quick-access {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(183, 28, 28, 0.1);
            border-left: 5px solid #B71C1C;
        }

        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quick-access-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .quick-access-card:hover {
            transform: translateY(-3px);
            background: #fff;
            box-shadow: 0 4px 12px rgba(183, 28, 28, 0.1);
        }

        .quick-access-card h4 {
            margin: 0.5rem 0;
            color: #B71C1C;
        }

        .quick-access-card p {
            color: #666;
            margin: 0;
        }

        /* Enhanced Search */
        .enhanced-search {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-input {
            flex: 1;
            position: relative;
        }

        .search-input input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid rgba(183, 28, 28, 0.2);
            border-radius: 8px;
        }

        .search-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #B71C1C;
        }

        .filters {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.8rem 1.5rem;
            border: 1px solid #B71C1C;
            background: white;
            color: #B71C1C;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #B71C1C;
            color: white;
        }

        /* Quick Jump Menu */
        .quick-jump {
            position: fixed;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(183, 28, 28, 0.1);
            z-index: 100;
        }

        .quick-jump ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .quick-jump li {
            margin: 0.5rem 0;
        }

        .quick-jump a {
            color: #B71C1C;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Stats Cards Styling */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
            border-left: 5px solid #B71C1C;
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #B71C1C;
            background: #ffebee;
            padding: 1rem;
            border-radius: 12px;
        }

        .stat-card .stat-info {
            flex: 1;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-number {
            font-size: 1.8rem;
            font-weight: 600;
            color: #B71C1C;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .stat-card .stat-number small {
            font-size: 1rem;
            color: #666;
            font-weight: normal;
        }

        /* Quick Access Grid */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem;
        }

        /* Add subtle animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }

            100% {
                transform: scale(1);
            }
        }

        .stat-card:hover i {
            animation: pulse 1s ease infinite;
        }

        /* Add responsive adjustments */
        @media (max-width: 768px) {
            .quick-stats {
                grid-template-columns: 1fr;
                margin: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-info span {
                font-size: 1.5rem;
            }
        }

        .booking-history-section {
            margin-top: 2rem;
        }

        .booking-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .booking-filters {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .custom-select,
        .custom-date {
            padding: 0.5rem;
            border: 1px solid rgba(183, 28, 28, 0.2);
            border-radius: 8px;
            min-width: 150px;
        }

        .booking-table {
            margin-top: 1rem;
        }

        .booking-table th {
            background: #B71C1C;
            color: white;
            padding: 1rem;
            font-weight: 500;
        }

        .booking-facility {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .rooms-badge {
            background: #ffebee;
            color: #B71C1C;
            padding: 0.2rem 0.5rem;
            border-radius: 15px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .schedule-info {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .schedule-info i {
            width: 16px;
            color: #666;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-warning {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-danger {
            background: #ffebee;
            color: #c62828;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon.success {
            background: #2e7d32;
        }

        .btn-icon.danger {
            background: #c62828;
        }

        .btn-icon.info {
            background: #1976D2;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info i {
            color: #666;
        }

        .booking-history-section {
            margin-top: 2rem;
        }

        .booking-filters {
            display: flex;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .booking-table th {
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .booking-details {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .booking-details p {
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="content">
            <!-- Quick Stats Dashboard -->
            <div class="quick-stats">
                <div class="stat-card total-facilities">
                    <i class="fas fa-building"></i>
                    <div class="stat-info">
                        <h3>Total Facilities</h3>
                        <div class="stat-number">
                            <?= number_format($conn->query("SELECT COUNT(*) FROM facilities")->fetch_row()[0]) ?>
                            <small>Facilities</small>
                        </div>
                    </div>
                </div>
                <div class="stat-card total-rooms">
                    <i class="fas fa-door-open"></i>
                    <div class="stat-info">
                        <h3>Total Rooms</h3>
                        <div class="stat-number">
                            <?= number_format($conn->query("SELECT COUNT(*) FROM rooms")->fetch_row()[0]) ?>
                            <small>Rooms</small>
                        </div>
                    </div>
                </div>
                <div class="stat-card total-capacity">
                    <i class="fas fa-users"></i>
                    <div class="stat-info">
                        <h3>Total Capacity</h3>
                        <div class="stat-number">
                            <?= number_format($conn->query("SELECT SUM(capacity) FROM rooms")->fetch_row()[0] ?? 0) ?>
                            <small>seats</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Search and Filters -->
            <div class="search-panel">
                <div class="search-container">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="facilitySearch" placeholder="Search facilities, rooms, or codes...">
                    </div>
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-th-large"></i> All Facilities
                        </button>
                        <button class="filter-btn" data-filter="with-rooms">
                            <i class="fas fa-door-open"></i> With Rooms
                        </button>
                        <button class="filter-btn" data-filter="no-rooms">
                            <i class="fas fa-building"></i> Without Rooms
                        </button>
                    </div>
                </div>
                <div class="view-options">
                    <button class="view-btn active" data-view="grid">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn" data-view="list">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>

            <!-- Quick Actions Bar -->
            <div class="quick-actions">
                <button class="action-btn" onclick="showAddFacilityModal()">
                    <i class="fas fa-plus"></i> New Facility
                </button>
                <button class="action-btn" onclick="showBulkOperations()">
                    <i class="fas fa-tasks"></i> Bulk Operations
                </button>
                <button class="action-btn" onclick="showMaintenanceSchedule()">
                    <i class="fas fa-calendar"></i> Maintenance Schedule
                </button>
                <button class="action-btn" onclick="exportFacilityData()">
                    <i class="fas fa-download"></i> Export Data
                </button>
            </div>

            <!-- Facilities Grid/List View -->
            <div class="facilities-view" id="facilitiesContainer">
                <div class="facilities-container">
                    <!-- Controls Header -->
                    <div class="controls-header">
                        <h2><i class="fas fa-door-open"></i> Facility Management</h2>
                        <div class="search-container">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search facilities..." value="<?php echo htmlspecialchars($searchParam); ?>">
                            </div>
                            <button class="action-btn add-btn" data-bs-toggle="modal" data-bs-target="#addFacilityModal">
                                <i class="fas fa-plus"></i> Add Facility
                            </button>
                        </div>
                    </div>

                    <!-- Facilities List -->
                    <?php if ($facilities->num_rows > 0): ?>
                        <?php while ($facility = $facilities->fetch_assoc()): ?>
                            <div class="facility-card" id="facility-<?= $facility['id'] ?>">
                                <div class="facility-header">
                                    <div class="facility-title">
                                        <h3><?= htmlspecialchars($facility['name']) ?></h3>
                                        <span class="facility-code"><?= htmlspecialchars($facility['code']) ?></span>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" data-bs-toggle="modal" data-bs-target="#editFacilityModal"
                                            data-facility-id="<?= $facility['id'] ?>"
                                            data-facility-name="<?= htmlspecialchars($facility['name']) ?>"
                                            data-facility-code="<?= htmlspecialchars($facility['code']) ?>"
                                            data-facility-description="<?= htmlspecialchars($facility['description']) ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-btn delete-btn" onclick="deleteFacility(<?= $facility['id'] ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>

                                <p class="facility-description"><?= htmlspecialchars($facility['description']) ?></p>

                                <!-- Rooms Section -->
                                <div class="rooms-section">
                                    <div class="rooms-header">
                                        <h4><i class="fas fa-door-open"></i> Rooms</h4>
                                        <button class="action-btn add-room-btn" data-bs-toggle="modal" data-bs-target="#addRoomModal"
                                            data-facility-id="<?= $facility['id'] ?>">
                                            <i class="fas fa-plus"></i> Add Room
                                        </button>
                                    </div>

                                    <?php
                                    $room_query = "SELECT * FROM rooms WHERE facility_id = ? ORDER BY room_number";
                                    $stmt = $conn->prepare($room_query);
                                    $stmt->bind_param("i", $facility['id']);
                                    $stmt->execute();
                                    $rooms = $stmt->get_result();
                                    ?>

                                    <div class="room-grid">
                                        <?php if ($rooms->num_rows > 0): ?>
                                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                                <div class="room-card">
                                                    <div class="room-info">
                                                        <h5>Room <?= htmlspecialchars($room['room_number']) ?></h5>
                                                        <span class="capacity">
                                                            <i class="fas fa-users"></i> <?= htmlspecialchars($room['capacity']) ?> seats
                                                        </span>
                                                    </div>
                                                    <div class="room-actions">
                                                        <button class="action-btn edit-room-btn" data-bs-toggle="modal"
                                                            data-bs-target="#editRoomModal"
                                                            data-room-id="<?= $room['id'] ?>"
                                                            data-room-number="<?= htmlspecialchars($room['room_number']) ?>"
                                                            data-room-capacity="<?= htmlspecialchars($room['capacity']) ?>"
                                                            data-room-description="<?= htmlspecialchars($room['description']) ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button class="action-btn delete-room-btn"
                                                            onclick="deleteRoom(<?= $room['id'] ?>)">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="empty-rooms">
                                                <p>No rooms added yet</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <p>No facilities found</p>
                            <span>Add your first facility to get started</span>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchParam) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

            <!-- New Interactive Features -->
            <div class="facility-tools">
                <div class="tool-card" onclick="showCapacityPlanner()">
                    <i class="fas fa-chart-pie"></i>
                    <h4>Capacity Planner</h4>
                    <p>Optimize room usage and capacity</p>
                </div>
                <div class="tool-card" onclick="showScheduleManager()">
                    <i class="fas fa-calendar-alt"></i>
                    <h4>Schedule Manager</h4>
                    <p>Manage facility schedules</p>
                </div>
                <div class="tool-card" onclick="showMaintenanceTracker()">
                    <i class="fas fa-tools"></i>
                    <h4>Maintenance Tracker</h4>
                    <p>Track facility maintenance</p>
                </div>
            </div>

            <!-- Quick Navigation Panel -->
            <div class="quick-nav-panel">
                <div class="nav-section">
                    <h4>Quick Jump</h4>
                    <ul id="facilityQuickLinks">
                        <!-- Dynamically populated -->
                    </ul>
                </div>
            </div>

            <div class="main-card booking-history-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Facility Booking History</h2>
                    <div class="booking-controls">
                        <div class="booking-filters">
                            <div class="form-group">
                                <select class="form-select custom-select" id="statusFilter">
                                    <option value="all">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="date" class="form-control custom-date" id="dateFilter">
                            </div>
                            <button class="btn-action export-btn" onclick="exportBookingHistory()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive booking-table-container">
                    <table class="table booking-table">
                        <thead>
                            <tr>
                                <th>Facility & Rooms</th>
                                <th>Requester</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingTableBody">
                            <?php
                            $booking_query = "
                                SELECT b.*, f.name as facility_name, u.full_name as user_name,
                                    GROUP_CONCAT(DISTINCT r.room_number) as room_numbers,
                                    DATE_FORMAT(b.booking_date, '%M %d, %Y') as formatted_date,
                                    DATE_FORMAT(b.start_time, '%h:%i %p') as formatted_start,
                                    DATE_FORMAT(b.end_time, '%h:%i %p') as formatted_end
                                FROM bookings b
                                JOIN facilities f ON b.facility_id = f.id
                                JOIN users u ON b.user_id = u.id
                                LEFT JOIN booking_rooms br ON b.id = br.booking_id
                                LEFT JOIN rooms r ON br.room_id = r.id
                                GROUP BY b.id
                                ORDER BY b.created_at DESC
                                LIMIT 10
                            ";
                            $bookings = $conn->query($booking_query);

                            while ($booking = $bookings->fetch_assoc()):
                                $status_class = match ($booking['status']) {
                                    'Confirmed' => 'success',
                                    'Cancelled' => 'danger',
                                    default => 'warning'
                                };
                            ?>
                                <tr>
                                    <td>
                                        <div class="booking-facility">
                                            <strong><?= htmlspecialchars($booking['facility_name']) ?></strong>
                                            <?php if ($booking['room_numbers']): ?>
                                                <span class="rooms-badge">
                                                    <i class="fas fa-door-open"></i>
                                                    <?= htmlspecialchars($booking['room_numbers']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($booking['user_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="schedule-info">
                                            <div class="date">
                                                <i class="fas fa-calendar"></i>
                                                <?= $booking['formatted_date'] ?>
                                            </div>
                                            <div class="time">
                                                <i class="fas fa-clock"></i>
                                                <?= $booking['formatted_start'] ?> - <?= $booking['formatted_end'] ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $status_class ?>">
                                            <?= htmlspecialchars($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($booking['status'] === 'Pending'): ?>
                                                <button class="btn-icon success" onclick="updateBookingStatus(<?= $booking['id'] ?>, 'Confirmed')"
                                                    data-tooltip="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn-icon danger" onclick="updateBookingStatus(<?= $booking['id'] ?>, 'Cancelled')"
                                                    data-tooltip="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-icon info" onclick="viewBookingDetails(<?= $booking['id'] ?>)"
                                                data-tooltip="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Booking Details Modal -->
            <div class="modal fade" id="bookingDetailsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Booking Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="bookingDetailsContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- ... Existing modal code ... -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteFacility(facilityId) {
            if (confirm('Are you sure you want to delete this facility? All rooms will also be deleted.')) {
                window.location.href = `?delete=${facilityId}`;
            }
        }

        function deleteRoom(roomId) {
            if (confirm('Are you sure you want to delete this room?')) {
                window.location.href = `?delete_room=${roomId}`;
            }
        }

        // Auto-populate edit modals
        document.addEventListener('DOMContentLoaded', function() {
            // Facility edit modal handler
            const editFacilityModal = document.getElementById('editFacilityModal');
            if (editFacilityModal) {
                editFacilityModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const modal = this;
                    modal.querySelector('#editFacilityId').value = button.dataset.facilityId;
                    modal.querySelector('#editFacilityName').value = button.dataset.facilityName;
                    modal.querySelector('#editFacilityCode').value = button.dataset.facilityCode;
                    modal.querySelector('#editFacilityDescription').value = button.dataset.facilityDescription;
                });
            }

            // Room edit modal handler
            const editRoomModal = document.getElementById('editRoomModal');
            if (editRoomModal) {
                editRoomModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const modal = this;
                    modal.querySelector('#editRoomId').value = button.dataset.roomId;
                    modal.querySelector('#editRoomNumber').value = button.dataset.roomNumber;
                    modal.querySelector('#editRoomCapacity').value = button.dataset.roomCapacity;
                    modal.querySelector('#editRoomDescription').value = button.dataset.roomDescription;
                });
            }
        });

        // Facility Quick Search
        function filterFacilities(searchText) {
            const cards = document.querySelectorAll('.facility-card');
            searchText = searchText.toLowerCase();
            cards.forEach(card => {
                const title = card.querySelector('.facility-title h3').textContent.toLowerCase();
                const code = card.querySelector('.facility-code').textContent.toLowerCase();
                const rooms = Array.from(card.querySelectorAll('.room-card')).map(room =>
                    room.textContent.toLowerCase()
                ).join(' ');
                if (title.includes(searchText) || code.includes(searchText) || rooms.includes(searchText)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterByType(type) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            const cards = document.querySelectorAll('.facility-card');
            cards.forEach(card => {
                if (type === 'all') {
                    card.style.display = 'block';
                    return;
                }
                const rooms = card.querySelectorAll('.room-card');
                const hasRooms = rooms.length > 0;
                if (type === 'with-rooms' && hasRooms) {
                    card.style.display = 'block';
                } else if (type === 'no-rooms' && !hasRooms) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Smooth scroll for quick jump
        document.querySelectorAll('.quick-jump a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const id = this.getAttribute('href');
                e.preventDefault();
                document.querySelector(id).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Utility function for debouncing
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Facility Quick Search
        const quickSearch = debounce((searchText) => {
            const cards = document.querySelectorAll('.facility-card');
            searchText = searchText.toLowerCase();
            cards.forEach(card => {
                const searchableContent = card.textContent.toLowerCase();
                card.style.display = searchableContent.includes(searchText) ? 'block' : 'none';
            });
        }, 300);

        // Initialize search
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('facilitySearch');
            searchInput.addEventListener('input', (e) => quickSearch(e.target.value));
        });

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', () => {
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });
        });

        // View Toggle
        function toggleView(viewType) {
            const container = document.getElementById('facilitiesContainer');
            container.className = `facilities-view ${viewType}-view`;
        }

        // Export Functionality
        function exportFacilityData() {
            // Implementation for exporting data
        }

        function updateBookingStatus(bookingId, status) {
            if (confirm(`Are you sure you want to mark this booking as ${status}?`)) {
                $.post('ajax/update_booking.php', {
                    booking_id: bookingId,
                    status: status
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to update booking status');
                    }
                });
            }
        }

        function viewBookingDetails(bookingId) {
            $.get('ajax/get_booking_details.php', {
                booking_id: bookingId
            }, function(response) {
                if (response.success) {
                    const booking = response.data;
                    $('#bookingDetailsContent').html(`
                        <div class="booking-details">
                            <p><strong>Facility:</strong> ${booking.facility_name}</p>
                            <p><strong>Room(s):</strong> ${booking.room_numbers}</p>
                            <p><strong>Booked By:</strong> ${booking.user_name}</p>
                            <p><strong>Date:</strong> ${booking.booking_date}</p>
                            <p><strong>Time:</strong> ${booking.start_time} - ${booking.end_time}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(booking.status)}">${booking.status}</span></p>
                            <p><strong>Booked On:</strong> ${booking.created_at}</p>
                        </div>
                    `);
                    $('#bookingDetailsModal').modal('show');
                }
            });
        }

        function getStatusColor(status) {
            switch (status) {
                case 'Confirmed':
                    return 'success';
                case 'Cancelled':
                    return 'danger';
                default:
                    return 'warning';
            }
        }

        // Add filter functionality
        $('#statusFilter, #dateFilter').on('change', function() {
            const status = $('#statusFilter').val();
            const date = $('#dateFilter').val();

            $.get('ajax/filter_bookings.php', {
                status: status,
                date: date
            }, function(response) {
                if (response.success) {
                    // Update table with filtered data
                    updateBookingTable(response.data);
                }
            });
        });
    </script>
    // ... Existing scripts ...
</body>

</html>
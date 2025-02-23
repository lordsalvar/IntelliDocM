<?php
session_start();
require_once 'database.php';
include 'system_log/activity_log.php';

// Validate user login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'moderator') {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$userActivity = 'User visited the dashboard';
logActivity($username, $userActivity);

// Fetch user's club membership
$user_id = $_SESSION['user_id'];
$club_query = "SELECT c.club_name FROM club_memberships cm JOIN clubs c ON cm.club_id = c.club_id WHERE cm.user_id = ?";
$stmt = $conn->prepare($club_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$club_result = $stmt->get_result();
$club_name = $club_result->fetch_assoc()['club_name'] ?? null;

// If no club is found, display a message
if (!$club_name) {
    echo "<p>You are not currently associated with any club.</p>";
    exit();
}

// Fetch proposals related to the user's club
$sql_proposals = "SELECT * FROM activity_proposals WHERE club_name = ?";
$stmt = $conn->prepare($sql_proposals);
$stmt->bind_param("s", $club_name);
$stmt->execute();
$proposals_result = $stmt->get_result();

// Fetch statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE club_name = '$club_name'")->fetch_row()[0],
    'pending' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE club_name = '$club_name' AND status = 'pending'")->fetch_row()[0],
    'confirmed' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE club_name = '$club_name' AND status = 'confirmed'")->fetch_row()[0],
    'cancelled' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE club_name = '$club_name' AND status = 'cancelled'")->fetch_row()[0]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link href="css/sidebar.css" rel="stylesheet" />
    <link href="css/dashboard.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .total .stat-icon {
            background: #e3f2fd;
            color: #1976d2;
        }

        .pending .stat-icon {
            background: #fff3e0;
            color: #f57c00;
        }

        .confirmed .stat-icon {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .cancelled .stat-icon {
            background: #ffebee;
            color: #c62828;
        }

        .proposals-grid {
            display: grid;
            gap: 1rem;
        }

        .proposal-card {
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .proposal-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .proposal-header {
            padding: 1.25rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .proposal-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .proposal-content {
            padding: 1.25rem;
        }

        .proposal-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .meta-item i {
            width: 16px;
            text-align: center;
            color: #1976d2;
        }

        .proposal-footer {
            padding: 1rem 1.25rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge.confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .status-badge i {
            font-size: 0.8rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .rejection-reason {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #ffebee;
            border-radius: 8px;
            color: #c62828;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box input {
            width: 90%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background: #1976d2;
            color: white;
            border-color: #1976d2;
        }

        .empty-state {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
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
            margin: 0;
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .no-results.show {
            display: block;
        }

        .designation {
            color: #1976d2;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .status-received {
            background: #e3f2fd;
            color: #1976d2;
        }

        .proposal-card[data-status="received"] .status-badge {
            background: #e3f2fd;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'moderator_sidebar.php'; ?>
        <div class="content">
            <h2 class="text-center">Submitted Proposals for <?= htmlspecialchars($club_name) ?></h2>
            
            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Proposals</h3>
                        <span class="stat-number"><?= $stats['total'] ?></span>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <span class="stat-number"><?= $stats['pending'] ?></span>
                    </div>
                </div>
                <div class="stat-card confirmed">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Confirmed</h3>
                        <span class="stat-number"><?= $stats['confirmed'] ?></span>
                    </div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Cancelled</h3>
                        <span class="stat-number"><?= $stats['cancelled'] ?></span>
                    </div>
                </div>
            </div>

             <!-- Search and Filter Controls -->
             <div class="search-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="proposalSearch" placeholder="Search proposals...">
                </div>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-status="all">All</button>
                    <button class="filter-btn" data-status="received">
                        <i class="fas fa-inbox"></i> Received
                    </button>
                    <button class="filter-btn" data-status="pending">
                        <i class="fas fa-clock"></i> Pending
                    </button>
                    <button class="filter-btn" data-status="confirmed">
                        <i class="fas fa-check"></i> Confirmed
                    </button>
                    <button class="filter-btn" data-status="cancelled">
                        <i class="fas fa-times"></i> Cancelled
                    </button>
                </div>
            </div>

            <div class="proposals-grid">
                <?php if ($proposals_result->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Proposals Found</h3>
                        <p>There are no activity proposals submitted yet.</p>
                    </div>
                <?php else: ?>
                    <?php while ($row = $proposals_result->fetch_assoc()): ?>
    <div class="proposal-card" data-status="<?= strtolower($row['status']) ?>">
        <div class="proposal-header">
            <h3 class="proposal-title"> <?= htmlspecialchars($row['activity_title']) ?> </h3>
            <span class="status-badge <?= strtolower($row['status']) ?>">
                <i class="fas fa-circle"></i> <?= ucfirst($row['status']) ?>
            </span>
        </div>

        <!-- Inserted Proposal Metadata Block -->
        <div class="proposal-content">
            <div class="proposal-meta">
                <div class="meta-item">
                    <i class="fas fa-users"></i>
                    <span><?= htmlspecialchars($row['club_name'] . ' (' . $row['acronym'] . ')') ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-tag"></i>
                    <span><?= htmlspecialchars($row['activity_type']) ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?= date('M d, Y', strtotime($row['activity_date'])) ?> - <?= date('M d, Y', strtotime($row['end_activity_date'])) ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($row['venue']) ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($row['applicant_name']) ?>
                        <?php if ($row['designation']): ?>
                            - <span class="designation"><?= htmlspecialchars($row['designation']) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="proposal-footer">
            <a href="modview_document.php?id=<?= $row['proposal_id'] ?>" class="btn btn-outline-primary">
                <i class="fas fa-eye"></i> View
            </a>
        </div>
    </div>
<?php endwhile; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        // Search functionality
        document.getElementById('proposalSearch').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const proposals = document.querySelectorAll('.proposal-card');
            let hasVisibleProposals = false;

            proposals.forEach(proposal => {
                const title = proposal.querySelector('h3').textContent.toLowerCase();
                const meta = proposal.querySelector('.proposal-meta').textContent.toLowerCase();
                const matches = title.includes(searchText) || meta.includes(searchText);
                proposal.style.display = matches ? 'grid' : 'none';
                if (matches) hasVisibleProposals = true;
            });

            // Show/hide no results message
            const noResults = document.querySelector('.no-results');
            if (!hasVisibleProposals && searchText) {
                if (!noResults) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-results';
                    noResultsDiv.innerHTML = `
                        <i class="fas fa-search"></i>
                        <h3>No Matching Proposals</h3>
                        <p>No proposals match your search criteria.</p>
                    `;
                    document.querySelector('.proposals-grid').appendChild(noResultsDiv);
                } else {
                    noResults.classList.add('show');
                }
            } else if (noResults) {
                noResults.classList.remove('show');
            }
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const status = this.dataset.status;

                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Filter proposals
                const proposals = document.querySelectorAll('.proposal-card');
                proposals.forEach(proposal => {
                    if (status === 'all' || proposal.dataset.status === status) {
                        proposal.style.display = 'grid';
                    } else {
                        proposal.style.display = 'none';
                    }
                });
            });
        });

        // Add your view, approve, and reject functions here
        function viewProposal(id) {
            window.location.href = `/main/intellidocm/view_document.php?id=${id}`;
        }

        function approveProposal(id) {
            if (confirm('Are you sure you want to approve this proposal?')) {
                // Add your approval logic here
            }
        }

        function rejectProposal(id) {
            if (confirm('Are you sure you want to reject this proposal?')) {
                // Add your rejection logic here
            }
        }
    </script>
</body>
</html>

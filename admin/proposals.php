<?php
session_start();
require_once '../database.php';

// Validate admin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM activity_proposals")->fetch_row()[0],
    'pending' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE status = 'pending'")->fetch_row()[0],
    'confirmed' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE status = 'confirmed'")->fetch_row()[0],
    'cancelled' => $conn->query("SELECT COUNT(*) FROM activity_proposals WHERE status = 'cancelled'")->fetch_row()[0]
];

// Fetch proposals with club information
$sql = "SELECT 
            ap.*,
            u.full_name as submitted_by,
            u.email as submitter_email,
            u.contact as submitter_contact
        FROM activity_proposals ap
        LEFT JOIN users u ON ap.user_id = u.id
        ORDER BY ap.submitted_date DESC";

$proposals = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Proposals - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/dashboard.css">

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
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .proposal-info h3 {
            margin: 0;
            color: #333;
            font-size: 1.1rem;
        }

        .proposal-meta {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .proposal-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-action:hover {
            opacity: 0.9;
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
            width: 100%;
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
    </style>
</head>

<body>
    <div class="dashboard">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="content">
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
                    <button class="filter-btn" data-status="pending">Pending</button>
                    <button class="filter-btn" data-status="confirmed">Confirmed</button>
                    <button class="filter-btn" data-status="cancelled">Cancelled</button>
                </div>
            </div>

            <!-- Proposals Grid -->
            <div class="proposals-grid">
                <?php if ($proposals->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Proposals Found</h3>
                        <p>There are no activity proposals submitted yet.</p>
                    </div>
                <?php else: ?>
                    <?php while ($proposal = $proposals->fetch_assoc()): ?>
                        <div class="proposal-card" data-status="<?= strtolower($proposal['status']) ?>">
                            <div class="proposal-info">
                                <h3><?= htmlspecialchars($proposal['activity_title']) ?></h3>
                                <div class="proposal-meta">
                                    <span><i class="fas fa-users"></i> <?= htmlspecialchars($proposal['club_name'] . ' (' . $proposal['acronym'] . ')') ?></span>
                                    <span><i class="fas fa-tag"></i> <?= htmlspecialchars($proposal['activity_type']) ?></span>
                                    <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($proposal['activity_date'])) ?> - <?= date('M d, Y', strtotime($proposal['end_activity_date'])) ?></span>
                                    <span><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($proposal['start_time'])) ?> - <?= date('h:i A', strtotime($proposal['end_time'])) ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($proposal['venue']) ?></span>
                                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($proposal['submitted_by']) ?></span>
                                </div>
                                <?php if ($proposal['status'] === 'cancelled' && $proposal['rejection_reason']): ?>
                                    <div class="rejection-reason">
                                        <i class="fas fa-info-circle"></i> Reason: <?= htmlspecialchars($proposal['rejection_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="proposal-actions">
                                <span class="proposal-status status-<?= strtolower($proposal['status']) ?>">
                                    <?= ucfirst($proposal['status']) ?>
                                </span>
                                <div class="action-buttons">
                                    <button class="btn-action" style="background: #1976d2;" onclick="viewProposal(<?= $proposal['proposal_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($proposal['status'] === 'pending'): ?>
                                        <button class="btn-action" style="background: #2e7d32;" onclick="approveProposal(<?= $proposal['proposal_id'] ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn-action" style="background: #c62828;" onclick="rejectProposal(<?= $proposal['proposal_id'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
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
            window.location.href = `view_proposal.php?id=${id}`;
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
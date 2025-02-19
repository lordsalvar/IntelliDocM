<?php
session_start();
require_once 'database.php';
include 'system_log/activity_log.php';

// Validate user login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'moderator') {
    header('Location: login.php');
    exit();
}

// Fetch user's full name
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$_SESSION['full_name'] = $user_data['full_name'];

// Fetch user's club membership
$club_query = "
    SELECT c.club_name 
    FROM club_memberships cm
    JOIN clubs c ON cm.club_id = c.club_id
    WHERE cm.user_id = ?
";
$stmt = $conn->prepare($club_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$club_result = $stmt->get_result();
$club_name = $club_result->fetch_assoc()['club_name'] ?? null;

// Fetch proposals if club exists
if ($club_name) {
    $sql_proposals = "SELECT * FROM activity_proposals WHERE club_name = ? ORDER BY activity_date DESC LIMIT 5";
    $stmt = $conn->prepare($sql_proposals);
    $stmt->bind_param("s", $club_name);
    $stmt->execute();
    $proposals_result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/moderator_sidebar.css">
    <link rel="stylesheet" href="css/moderator_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/activity_logger.js" defer></script>
    <style>
        #activityChart {
            max-width: 400px;
            max-height: 300px;
            display: block;
            margin: auto;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <?php include 'moderator_sidebar.php'; ?>
        <div class="content">
            <div class="card">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
                <p>This is your dashboard where you can manage your documents and profile.</p>
            </div>
            <div class="card">
                <h3>Recent Documents</h3>
                <div class="recent-documents">
                    <ul>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Proposal for Intramurals</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Budget Report for Intramurals</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Proposal for IT WEEK</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Minutes of the Last CCIS Meeting</a></li>
                        <li><i class="fas fa-file-alt"></i> <a href="#">Club Fundraising Proposal</a></li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <button style="padding: 1rem; border: none;">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                    <button style="padding: 1rem; border: none;">
                        <i class="fas fa-folder"></i> View All Documents
                    </button>
                </div>
            </div>
            <div class="card">
                <h3>Recent Club Activities</h3>
                <canvas id="activityChart"></canvas>
            </div>
            <div class="card">
                <h3>Approved Activities</h3>
                <div id="activityTable"></div>
            </div>
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <button style="padding: 1rem; border: none;">
                        <i class="fas fa-folder"></i> View All Proposals
                    </button>
                </div>
            </div>
            <div class="proposals-card">
                <div class="proposals-header">
                    <h3 class="proposals-title">
                        <i class="fas fa-clipboard-list"></i>
                        Recent Club Activity Proposals for <?= htmlspecialchars($club_name ?? 'No Club') ?>
                    </h3>
                </div>

                <?php if (isset($proposals_result) && $proposals_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="proposals-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-file-alt"></i> Title</th>
                                    <th><i class="fas fa-calendar"></i> Date</th>
                                    <th><i class="fas fa-clock"></i> Time</th>
                                    <th><i class="fas fa-info-circle"></i> Status</th>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $proposals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Title"><?= htmlspecialchars($row['activity_title']) ?></td>
                                        <td data-label="Date"><?= date('M d, Y', strtotime($row['activity_date'])) ?></td>
                                        <td data-label="Time"><?= date('h:i A', strtotime($row['start_time'])) ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge <?= strtolower($row['status']) ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= htmlspecialchars($row['status'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <a href="modview_document.php?id=<?= $row['proposal_id'] ?>"
                                                class="view-btn"
                                                onclick="logDocumentViewActivity('<?= htmlspecialchars($row['activity_title']) ?>', <?= $row['proposal_id'] ?>)">
                                                <i class="fas fa-eye">View</i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No activity proposals found for your club.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['IT week', 'Intramurals', 'Mini Olympics'],
                datasets: [{
                    label: 'Number of Participants',
                    data: [120, 190, 100],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetchActivities();

            function fetchActivities() {
                fetch('?api=1') // API request to the same page
                    .then(response => response.json())
                    .then(data => displayActivities(data))
                    .catch(error => console.error('Error fetching activities:', error));
            }

            function displayActivities(activities) {
                const table = document.createElement('table');
                table.className = 'table table-striped';
                const thead = table.createTHead();
                const headerRow = thead.insertRow();
                ['Activity Title', 'Date', 'Status', 'Actions'].forEach(text => {
                    const headerCell = document.createElement('th');
                    headerCell.textContent = text;
                    headerRow.appendChild(headerCell);
                });

                const tbody = document.createElement('tbody');
                activities.forEach(activity => {
                    const row = tbody.insertRow();
                    row.insertCell().textContent = activity.activity_title;
                    row.insertCell().textContent = activity.activity_date;

                    const statusCell = row.insertCell();
                    const statusSelect = document.createElement('select');
                    ['Completed', 'Re-scheduled', 'Cancelled'].forEach(status => {
                        const option = document.createElement('option');
                        option.value = status;
                        option.textContent = status;
                        option.selected = activity.status === status;
                        statusSelect.appendChild(option);
                    });
                    statusCell.appendChild(statusSelect);

                    const actionCell = row.insertCell();
                    const updateButton = document.createElement('button');
                    updateButton.textContent = 'Update Status';
                    updateButton.className = 'btn btn-primary';
                    updateButton.onclick = () => updateStatus(activity.activity_id, statusSelect.value);
                    actionCell.appendChild(updateButton);
                });

                table.appendChild(tbody);
                document.getElementById('activityTable').innerHTML = ''; // Clear previous entries
                document.getElementById('activityTable').appendChild(table);
            }

            function updateStatus(activityId, status) {
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `update=true&activityId=${activityId}&status=${status}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            fetchActivities(); // Refresh the activities list
                        } else {
                            alert('Error updating status');
                        }
                    })
                    .catch(error => console.error('Error updating status:', error));
            }
        });
    </script>
</body>

</html>
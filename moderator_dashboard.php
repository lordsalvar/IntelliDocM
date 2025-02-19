<?php
// Check if this is an API request for activities or an update request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['api'])) {
    // Static data for demonstration purposes
    $activities = [
        ['activity_id' => 1, 'activity_title' => 'IT Week', 'activity_date' => '2025-04-15', 'status' => 'Completed'],
        ['activity_id' => 2, 'activity_title' => 'Intramurals', 'activity_date' => '2025-05-20', 'status' => 'Re-scheduled'],
        ['activity_id' => 3, 'activity_title' => 'Mini Olympics', 'activity_date' => '2025-06-11', 'status' => 'Cancelled'],
        ['activity_id' => 4, 'activity_title' => 'Event 2', 'activity_date' => '2025-04-15', 'status' => 'Completed'],
        ['activity_id' => 5, 'activity_title' => 'Event 3', 'activity_date' => '2025-05-20', 'status' => 'Re-scheduled'],
        ['activity_id' => 6, 'activity_title' => 'Event 4', 'activity_date' => '2025-06-11', 'status' => 'Cancelled']
    ];

    echo json_encode($activities);
    return; // End the execution after API response
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Placeholder for updating activity status
    // Here you would have code to update the activity status in the database
    // For demonstration, we simulate a successful update response
    echo json_encode(['success' => true, 'message' => 'Status updated successfully!']);
    return;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
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
        <?php include 'includes/sidebar.php'; ?>
        <div class="content">
            <div class="card">
                <h2>Welcome, Moderator</h2>
                <p>This is your dashboard where you can review and manage club activity proposals.</p>
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
                        Recent Club Activity Proposals
                    </h3>
                </div>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>No activity proposals found.</p>
                </div>
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
                fetch('?api=1')  // API request to the same page
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

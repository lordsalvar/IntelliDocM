<?php
// Check if this is an API request for activities or an update request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['api'])) {
    // Static data for demonstration purposes
    $activities = [
        ['activity_id' => 1, 'activity_title' => 'Spring Festival', 'activity_date' => '2025-04-15', 'status' => 'Completed'],
        ['activity_id' => 2, 'activity_title' => 'Tech Conference', 'activity_date' => '2025-05-20', 'status' => 'Scheduled'],
        ['activity_id' => 3, 'activity_title' => 'Art Exhibition', 'activity_date' => '2025-06-11', 'status' => 'Cancelled']
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
    <title>Activity Updates</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/actPage.css">

</head>
<body>
    <header>
        <?php include 'includes/clientnavbar.php'; ?>
    </header>
    <div class="container mt-5">
        <h2>Approved Activities</h2>
        <div id="activityTable"></div>
    </div>
    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
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

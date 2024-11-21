<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/calendar.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>

    <header>
        <h2>Admin User</h2>
    </header>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <a href="Admin-Dashboard.php">Dashboard</a>
        <a href="Calendar.php">Calendar</a>
        <a href="users.html">User Management</a>
        <a href="logout.html">Logout</a>
        <h4 class="mt-4">Forms</h4>
        <button type="button" class="btn btn-light btn-block" data-toggle="modal" data-target="#activityProposalModal">View Activity Proposal Forms</button>
        <button type="button" class="btn btn-light btn-block" data-toggle="modal" data-target="#bookingFormModal">View Booking Forms</button>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container my-5">
            <h2>Admin Dashboard</h2>

            <!-- Activity Proposals Section -->
            <section class="my-7">
                <h3>Activity Proposals</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Club</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample Row -->
                        <tr>
                            <td>Sports Fest</td>
                            <td>Sports Club</td>
                            <td><span class="badge badge-secondary">Received</span></td>
                            <td>
                                <button class="btn btn-success" onclick="approveActivity(this)">Approve</button>
                                <!-- Disapprove button opens the modal -->
                                <button class="btn btn-danger" data-toggle="modal" data-target="#disapproveModal" onclick="setDisapproveContext('Sports Fest', 'Sports Club')">Disapprove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <!-- Disapprove Modal -->
    <div class="modal fade" id="disapproveModal" tabindex="-1" role="dialog" aria-labelledby="disapproveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="disapproveModalLabel">Disapprove Activity</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Activity:</strong> <span id="activityName">Activity Name</span></p>
                    <p><strong>Club:</strong> <span id="clubName">Club Name</span></p>
                    <p>Please provide a reason for disapproval:</p>
                    <input type="text" class="form-control" id="disapprovalReason" placeholder="Reason for disapproval" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="submitDisapproval()">Submit Disapproval</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Proposal Form Modal -->
    <div class="modal fade" id="activityProposalModal" tabindex="-1" role="dialog" aria-labelledby="activityProposalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activityProposalModalLabel">Activity Proposal Forms</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Date Submitted</th>
                                <th>Club</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample Row -->
                            <tr>
                                <td>Sports Fest Proposal</td>
                                <td>2024-11-15</td>
                                <td>Sports Club</td>
                                <td><button type="button" class="btn btn-info btn-sm">View Details</button></td>
                            </tr>
                            <tr>
                                <td>Art Exhibit Proposal</td>
                                <td>2024-11-14</td>
                                <td>Art Club</td>
                                <td><button type="button" class="btn btn-info btn-sm">View Details</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Form Modal -->
    <div class="modal fade" id="bookingFormModal" tabindex="-1" role="dialog" aria-labelledby="bookingFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingFormModalLabel">Booking Forms</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Date Submitted</th>
                                <th>Club</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample Row -->
                            <tr>
                                <td>Auditorium Booking</td>
                                <td>2024-11-13</td>
                                <td>Drama Club</td>
                                <td><button type="button" class="btn btn-info btn-sm">View Details</button></td>
                            </tr>
                            <tr>
                                <td>Sports Field Booking</td>
                                <td>2024-11-12</td>
                                <td>Sports Club</td>
                                <td><button type="button" class="btn btn-info btn-sm">View Details</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Cor Jesu College. All Rights Reserved.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function setDisapproveContext(activity, club) {
            // Set activity and club names in modal
            document.getElementById('activityName').textContent = activity;
            document.getElementById('clubName').textContent = club;
        }

        function submitDisapproval() {
            const reason = document.getElementById('disapprovalReason').value;

            if (!reason) {
                alert('Please enter a reason for disapproval.');
                return;
            }

            // Assuming the row data would dynamically update with backend logic
            // For now, we display the disapproval status and reason on the page (for demonstration)
            const activityRow = document.querySelector(`tr:contains(${document.getElementById('activityName').textContent})`);
            activityRow.querySelector('td:nth-child(3)').innerHTML = '<span class="badge badge-danger">Disapproved</span>';
            activityRow.querySelector('td:nth-child(4)').innerHTML = `<small>Reason: ${reason}</small>`;

            // Close the modal and clear the input
            $('#disapproveModal').modal('hide');
            document.getElementById('disapprovalReason').value = '';
        }
    </script>

</body>

</html>
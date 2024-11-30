<?php
require_once 'database.php';
// Client Calendar.php

date_default_timezone_set('Asia/Manila');

// Database connection (Assuming a MySQL database)

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the current date or the passed month/year parameters
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');

// Handle month navigation
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'prev') {
        $month--;
        if ($month < 1) {
            $month = 12;
            $year--;
        }
    } elseif ($_GET['action'] == 'next') {
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }
    }
}

// Calculate the first day of the month and number of days
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$monthName = date('F', $firstDayOfMonth);
$dayOfWeek = date('N', $firstDayOfMonth);

// Get today's date for highlighting
$today = date('Y-m-d');

// Prepare for output
$calendar = "<div class='container mt-5'>";
$calendar .= "<div class='card shadow-lg border-0'>";
$calendar .= "<div class='card-header bg-primary text-white d-flex justify-content-between align-items-center'>";
$calendar .= "<button class='btn btn-light' onclick=\"window.location.href='?action=prev&month=$month&year=$year'\">&laquo; Previous</button>";
$calendar .= "<h3 class='text-center mb-0'>$monthName $year</h3>";
$calendar .= "<button class='btn btn-light' onclick=\"window.location.href='?action=next&month=$month&year=$year'\">Next &raquo;</button>";
$calendar .= "</div>";

$calendar .= "<div class='card-body p-0'>";
$calendar .= "<table class='table table-bordered table-hover m-0 text-center table-responsive-sm'>";
$calendar .= "<thead class='thead-light'>";
$calendar .= "<tr>";
$calendar .= "<th class='py-3'>Monday</th>";
$calendar .= "<th class='py-3'>Tuesday</th>";
$calendar .= "<th class='py-3'>Wednesday</th>";
$calendar .= "<th class='py-3'>Thursday</th>";
$calendar .= "<th class='py-3'>Friday</th>";
$calendar .= "<th class='py-3 text-danger'>Saturday</th>";
$calendar .= "<th class='py-3 text-danger'>Sunday</th>";
$calendar .= "</tr>";
$calendar .= "</thead>";
$calendar .= "<tbody>";

$currentDay = 1;
$calendar .= '<tr>';

// Fill the empty days of the week before the first day of the month
if ($dayOfWeek != 1) {
    for ($i = 1; $i < $dayOfWeek; $i++) {
        $calendar .= '<td class="bg-light"></td>';
    }
}

// Print the days of the month
while ($currentDay <= $daysInMonth) {
    if ($dayOfWeek > 7) {
        $dayOfWeek = 1;
        $calendar .= '</tr><tr>';
    }

    $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
    $highlightClass = ($currentDate == $today) ? 'bg-warning ' : 'bg-white';

    // Fetch events from the database for the current day
    $eventHtml = "";
    $sql = "SELECT event_title, event_description FROM events WHERE event_start_date <= '$currentDate' AND event_end_date >= '$currentDate'";
    $result = $conn->query($sql);
    if ($result === false) {
        die("SQL error: " . $conn->error);
    }

    $blocked = $result->num_rows > 0;
    if ($blocked) {
        $highlightClass = 'bg-white'; // Keep the background white for consistency
        while ($row = $result->fetch_assoc()) {
            if ($row['event_title'] === 'Blocked Event') {
                $eventHtml .= "<div class='event text-info mt-2'><i class='fas fa-lock'></i> <strong>{$row['event_title']}</strong><br><small>{$row['event_description']}</small></div>";
            } else {
                $eventHtml .= "<div class='event text-info mt-2'><i class='fas fa-calendar-alt'></i> <strong>{$row['event_title']}</strong><br><small>{$row['event_description']}</small></div>";
            }
        }
    }

    $modalFunction = $blocked ? "showEventDetailsModal('$currentDate')" : "showRequestModal('$currentDate')";
    $calendar .= "<td class='align-middle $highlightClass' onclick=\"$modalFunction\">";
    $calendar .= "<div class='day-number badge badge-pill badge-light py-1 px-2 mb-2'>$currentDay</div>";
    $calendar .= "<div class='mt-2'>$eventHtml</div>";
    $calendar .= "</td>";

    $currentDay++;
    $dayOfWeek++;
}

// Fill the empty days of the week after the last day of the month
if ($dayOfWeek != 1) {
    for ($i = $dayOfWeek; $i <= 7; $i++) {
        $calendar .= '<td class="bg-light"></td>';
    }
}

$calendar .= '</tr>';
$calendar .= "</tbody>";
$calendar .= "</table>";
$calendar .= "</div>";
$calendar .= "</div>";
$calendar .= "</div>";

// Output the final calendar
echo $calendar;
?>

<!-- Modal for Requesting Event Block -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="requestModalLabel">Request Date Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="requestEventForm">
                    <div class="mb-3">
                        <label for="requestTitle" class="form-label">Event Title</label>
                        <input type="text" class="form-control" id="requestTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="requestDescription" class="form-label">Event Description</label>
                        <textarea class="form-control" id="requestDescription"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="requestStartDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="requestStartDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="requestEndDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="requestEndDate" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="requestEventBlock()">Request Block</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Viewing Event Details -->
<div class="modal fade" id="viewEventModal" tabindex="-1" aria-labelledby="viewEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewEventModalLabel">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventDetailsBody">
                <!-- Event details will be inserted here via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Attach click event to each calendar cell to open the appropriate modal
        window.showRequestModal = function(date) {
            $('#requestModalLabel').text('Request Block for ' + date);
            $('#requestStartDate').val(date);
            $('#requestEndDate').val(date);
            $('#requestEventForm').data('date', date);
            $('#requestModal').modal('show');
        }

        window.showEventDetailsModal = function(date) {
            $.ajax({
                type: 'POST',
                url: 'fetch_event_details.php',
                data: {
                    event_date: date
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            let eventDetails = '';
                            result.events.forEach(event => {
                                eventDetails += `<h5>${event.event_title}</h5>`;
                                eventDetails += `<p>${event.event_description}</p>`;
                                eventDetails += `<p><strong>Start Date:</strong> ${event.event_start_date}</p>`;
                                eventDetails += `<p><strong>End Date:</strong> ${event.event_end_date}</p>`;
                                eventDetails += '<hr>';
                            });
                            $('#eventDetailsBody').html(eventDetails);
                            $('#viewEventModal').modal('show');
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (e) {
                        alert('Unexpected response from the server: ' + response);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    alert('Failed to fetch event details due to server error. Please try again.');
                }
            });
        }
    });

    function requestEventBlock() {
        const title = document.getElementById('requestTitle').value.trim();
        const description = document.getElementById('requestDescription').value.trim();
        const startDate = document.getElementById('requestStartDate').value.trim();
        const endDate = document.getElementById('requestEndDate').value.trim();

        // Validate that no fields are empty
        if (!title || !startDate || !endDate) {
            alert('Please fill in all required fields.');
            return;
        }

        $.ajax({
            type: 'POST',
            url: 'request_event.php',
            data: {
                event_title: title,
                event_description: description,
                event_start_date: startDate,
                event_end_date: endDate
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === 'success') {
                        alert(result.message);
                        $('#requestModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (e) {
                    alert('Unexpected response from the server: ' + response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                alert('Failed to send request due to server error. Please try again.');
            }
        });
    }
</script>

<?php
// Close the database connection
$conn->close();
?>
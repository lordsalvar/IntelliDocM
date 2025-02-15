<?php
session_start();
require_once 'database.php';

// Validate admin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch activity data
$events = [];
$sql = "SELECT activity_title, activity_date, end_activity_date, status, club_name FROM activity_proposals";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Set event color based on status
        $color = match ($row['status']) {
            'cancelled' => '#dc3545',
            'confirmed' => '#28a745',
            'pending' => '#ffc107',
            default => '#007bff'
        };

        $events[] = [
            "title" => $row['activity_title'] . " (" . $row['club_name'] . ")",
            "start" => date('Y-m-d', strtotime($row['activity_date'])),
            "end" => date('Y-m-d', strtotime($row['end_activity_date'] . ' +1 day')),
            "color" => $color,
            "status" => $row['status']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Calendar - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .calendar-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(183, 28, 28, 0.1);
            border-left: 5px solid #B71C1C;
            margin: 2rem;
        }

        .calendar-header {
            margin-bottom: 2rem;
            color: #B71C1C;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .calendar-header h2 {
            margin: 0;
        }

        .legend {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: white;
        }

        .legend-pending {
            background: #ffc107;
        }

        .legend-confirmed {
            background: #28a745;
        }

        .legend-cancelled {
            background: #dc3545;
        }

        .legend-default {
            background: #007bff;
        }

        .fc .fc-button-primary {
            background-color: #B71C1C;
            border-color: #B71C1C;
        }

        .fc .fc-button-primary:hover {
            background-color: #D32F2F;
            border-color: #D32F2F;
        }

        .fc .fc-button-active {
            background-color: #7F0000 !important;
            border-color: #7F0000 !important;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <?php include 'includes/admin_sidebar.php'; ?>
        <div class="content">
            <div class="calendar-container">
                <div class="calendar-header">
                    <h2><i class="fas fa-calendar-alt"></i> Activity Calendar</h2>
                </div>
                <div class="legend">
                    <div class="legend-item legend-pending">
                        <i class="fas fa-circle"></i> Pending
                    </div>
                    <div class="legend-item legend-confirmed">
                        <i class="fas fa-circle"></i> Confirmed
                    </div>
                    <div class="legend-item legend-cancelled">
                        <i class="fas fa-circle"></i> Cancelled
                    </div>
                    <div class="legend-item legend-default">
                        <i class="fas fa-circle"></i> Others
                    </div>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($events); ?>,
                height: 'auto',
                eventDidMount: function(info) {
                    info.el.title = info.event.title;
                }
            });
            calendar.render();
        });
    </script>
</body>

</html>
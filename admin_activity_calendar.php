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

// Add these queries right after the initial $events array declaration
$stats = [
    'approved' => $conn->query("SELECT COUNT(*) as count FROM activity_proposals WHERE status = 'confirmed'")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM activity_proposals WHERE status = 'pending'")->fetch_assoc()['count'],
    'received' => $conn->query("SELECT COUNT(*) as count FROM activity_proposals WHERE status = 'received'")->fetch_assoc()['count'],
    'rejected' => $conn->query("SELECT COUNT(*) as count FROM activity_proposals WHERE status = 'cancelled'")->fetch_assoc()['count']
];

// Fetch both activity proposals and bookings
$events = [];

// First, get activities from activity_proposals table with proper date formatting
$activities_sql = "SELECT 
    activity_title,
    acronym,
    DATE_FORMAT(activity_date, '%Y-%m-%d') as start_date,
    DATE_FORMAT(end_activity_date, '%Y-%m-%d') as end_date,
    status,
    start_time,
    end_time
    FROM activity_proposals 
    WHERE activity_date IS NOT NULL 
    AND end_activity_date IS NOT NULL";

$activities_result = $conn->query($activities_sql);

if ($activities_result && $activities_result->num_rows > 0) {
    while ($row = $activities_result->fetch_assoc()) {
        // Create an event that spans multiple days
        $start_datetime = $row['start_date'] . ($row['start_time'] ? ' ' . $row['start_time'] : '');
        $end_datetime = $row['end_date'] . ($row['end_time'] ? ' ' . $row['end_time'] : ' 23:59:59');

        $events[] = [
            "title" => "Activity: " . $row['activity_title'] . " (" . ($row['acronym'] ?? 'N/A') . ")",
            "start" => $start_datetime,
            "end" => $end_datetime,
            "color" => match ($row['status']) {
                'cancelled' => '#dc3545',
                'confirmed' => '#28a745',
                'pending' => '#ffc107',
                'received' => '#0dcaf0',
                default => '#007bff'
            },
            "status" => $row['status'],
            "type" => "activity",
            "allDay" => empty($row['start_time']) && empty($row['end_time'])
        ];
    }
}

// Then, get facility bookings
$bookings_sql = "SELECT 
    b.id, b.booking_date, b.start_time, b.end_time, b.status,
    f.name as facility_name,
    GROUP_CONCAT(r.room_number) as room_numbers,
    c.acronym
    FROM bookings b
    LEFT JOIN facilities f ON b.facility_id = f.id
    LEFT JOIN booking_rooms br ON b.id = br.booking_id
    LEFT JOIN rooms r ON br.room_id = r.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN club_memberships cm ON u.id = cm.user_id
    LEFT JOIN clubs c ON cm.club_id = c.club_id
    GROUP BY b.id";

$bookings_result = $conn->query($bookings_sql);

if ($bookings_result && $bookings_result->num_rows > 0) {
    while ($row = $bookings_result->fetch_assoc()) {
        $roomInfo = $row['room_numbers'] ? " (Rooms: " . $row['room_numbers'] . ")" : "";
        $title = "Booking: " . $row['facility_name'] . $roomInfo;
        if ($row['acronym']) {
            $title .= " - " . $row['acronym'];
        }

        $startDateTime = $row['booking_date'] . ' ' . $row['start_time'];
        $endDateTime = $row['booking_date'] . ' ' . $row['end_time'];

        $events[] = [
            "title" => $title,
            "start" => date('Y-m-d\TH:i:s', strtotime($startDateTime)),
            "end" => date('Y-m-d\TH:i:s', strtotime($endDateTime)),
            "color" => match ($row['status']) {
                'Cancelled' => '#ff6b6b',
                'Confirmed' => '#51cf66',
                'Pending' => '#ffd43b',
                default => '#339af0'
            },
            "status" => $row['status'],
            "type" => "booking"
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
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

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

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-details h3 {
            margin: 0;
            font-size: 1rem;
            color: #666;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
        }

        /* Stat Card Variants */
        .stat-card.approved {
            border-left: 4px solid #28a745;
        }

        .stat-card.approved .stat-icon {
            color: #28a745;
            background: #e8f5e9;
        }

        .stat-card.pending {
            border-left: 4px solid #ffc107;
        }

        .stat-card.pending .stat-icon {
            color: #ffc107;
            background: #fff3e0;
        }

        .stat-card.received {
            border-left: 4px solid #007bff;
        }

        .stat-card.received .stat-icon {
            color: #007bff;
            background: #e3f2fd;
        }

        .stat-card.rejected {
            border-left: 4px solid #dc3545;
        }

        .stat-card.rejected .stat-icon {
            color: #dc3545;
            background: #ffebee;
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

                <!-- Replace the legend with stats cards -->
                <div class="stats-row mb-4">
                    <div class="stat-card approved">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Approved</h3>
                            <span class="stat-number"><?php echo $stats['approved']; ?></span>
                        </div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Pending</h3>
                            <span class="stat-number"><?php echo $stats['pending']; ?></span>
                        </div>
                    </div>
                    <div class="stat-card received">
                        <div class="stat-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Received</h3>
                            <span class="stat-number"><?php echo $stats['received']; ?></span>
                        </div>
                    </div>
                    <div class="stat-card rejected">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Rejected</h3>
                            <span class="stat-number"><?php echo $stats['rejected']; ?></span>
                        </div>
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
                initialView: 'timeGridWeek', // Changed to show time grid by default
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($events); ?>,
                slotMinTime: '07:00:00', // Start time of day
                slotMaxTime: '21:00:00', // End time of day
                height: 'auto',
                views: {
                    timeGridWeek: {
                        slotMinTime: '07:00:00',
                        slotMaxTime: '21:00:00',
                        displayEventEnd: true
                    },
                    dayGridMonth: {
                        displayEventEnd: true
                    }
                },
                eventDisplay: 'block',
                displayEventTime: true,
                eventDidMount: function(info) {
                    const event = info.event;
                    const type = event.extendedProps.type === 'booking' ? 'Booking' : 'Activity';
                    let dateInfo = '';

                    if (event.allDay) {
                        dateInfo = `\nFrom: ${new Date(event.start).toLocaleDateString()} To: ${new Date(event.end).toLocaleDateString()}`;
                    } else {
                        dateInfo = `\nFrom: ${event.start.toLocaleString()} To: ${event.end.toLocaleString()}`;
                    }

                    info.el.title = `${type}: ${event.title}\nStatus: ${event.extendedProps.status}${dateInfo}`;
                },
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: true
                }
            });
            calendar.render();
        });
    </script>
</body>

</html>
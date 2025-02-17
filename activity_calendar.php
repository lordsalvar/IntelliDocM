    <?php
    session_start();
    require_once 'database.php';

    // Validate user login
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
        header('Location: login.php');
        exit();
    }

    // Fetch activity data
    $events = [];
    $sql = "SELECT activity_title, activity_date, end_activity_date, status FROM activity_proposals";
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
                "title" => $row['activity_title'] . " (" . ucfirst($row['status']) . ")",
                "start" => date('Y-m-d', strtotime($row['activity_date'])),
                "end" => date('Y-m-d', strtotime($row['end_activity_date'] . ' +1 day')),
                "color" => $color
            ];
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Activity Calendar - IntelliDoc</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
        <link rel="stylesheet" href="css/sidebar.css">
        <link rel="stylesheet" href="css/dashboard.css">
        <style>
            .calendar-container {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 8px 20px rgba(183, 28, 28, 0.1);
                border-left: 5px solid #B71C1C;
                margin: 2rem;
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
            <?php include 'includes/sidebar.php'; ?>
            <div class="content">
                <div class="calendar-container">
                    <h2><i class="fas fa-calendar-alt"></i> Activity Calendar</h2>
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
<?php
// facility_calendar.php
session_start([
    'cookie_lifetime' => 3600,
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

include '../system_log/activity_log.php';
include '../database.php';

// Prevent session fixation
session_regenerate_id(true);

// Check user role
if ($_SESSION['role'] !== 'client') {
    header('Location: ../login.php');
    exit();
}
$username = $_SESSION['username'];
$userActivity = 'User visited Facility Calendar Page';
logActivity($username, $userActivity);

// Fetch facility availability data
$events = [];
$conn = getDbConnection(); // Assumes your database connection function is defined in database.php

// Example SQL: Adjust table names and column names as needed.
$sql = "SELECT fa.*, f.name 
        FROM facility_availability fa 
        JOIN facilities f ON fa.facility_id = f.id";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Determine the event color and title based on the status.
        if ($row['status'] === 'blocked') {
            $color = '#dc3545'; // red for blocked
            $title = $row['name'] . " (Blocked)";
        } elseif ($row['status'] === 'booked' || $row['status'] === 'unavailable') {
            $color = '#ffc107'; // amber for booked/unavailable
            $title = $row['name'] . " (Booked)";
        } else {
            $color = '#28a745'; // green for available (if needed)
            $title = $row['name'] . " (Available)";
        }
        // Convert the date to ISO format (YYYY-MM-DD)
        $date = date('Y-m-d', strtotime($row['date']));
        $events[] = [
            "title" => $title,
            "start" => $date,
            "color" => $color
        ];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Facility Availability Calendar</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- FullCalendar CSS -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
  <!-- Google Font: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f8f9fa;
      padding-top: 20px;
    }
    .container {
      max-width: 1200px;
      margin: auto;
    }
    h1 {
      margin-bottom: 30px;
    }
    /* Calendar styling */
    #calendar {
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
      min-height: 600px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="text-center">Facility Availability Calendar</h1>
    <!-- FullCalendar Container -->
    <div id="calendar"></div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- FullCalendar JS -->
  <script src="
https://cdn.jsdelivr.net/npm/fullcalendar@6.1/index.global.min.js
"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Get events from PHP
      var events = <?php echo json_encode($events); ?>;
      var calendarEl = document.getElementById('calendar');
      var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: events,
        height: 600
      });
      calendar.render();
    });
  </script>
</body>
</html>

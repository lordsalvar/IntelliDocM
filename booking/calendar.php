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
$userActivity = 'User visited Activity Calendar Page';
logActivity($username, $userActivity);

// Fetch activity data
$events = [];
$conn = getDbConnection();

// Adjust this SQL based on your table and column names
$sql = "SELECT activity_title, activity_date, end_activity_date, status FROM activity_proposals";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Set event color based on the status
    if ($row['status'] === 'cancelled') {
      $color = '#dc3545'; // red for cancelled
      $statusText = "Cancelled";
    } elseif ($row['status'] === 'confirmed') {
      $color = '#28a745'; // green for confirmed
      $statusText = "Confirmed";
    } elseif ($row['status'] === 'pending') {
      $color = '#ffc107'; // amber for pending
      $statusText = "Pending";
    } else {
      $color = '#007bff'; // default blue
      $statusText = ucfirst($row['status']);
    }

    // Format dates to ISO format (YYYY-MM-DD)
    $startDate = date('Y-m-d', strtotime($row['activity_date']));
    $endDate = date('Y-m-d', strtotime($row['end_activity_date']));

    // Append status to title if desired
    $title = $row['activity_title'] . " (" . $statusText . ")";

    $events[] = [
      "title" => $title,
      "start" => $startDate,
      "end"   => $endDate,
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
  <title>Activity Calendar</title>
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
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
      min-height: 600px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1 class="text-center">Activity Calendar</h1>
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
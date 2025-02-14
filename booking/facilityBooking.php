<?php
session_start([
    'cookie_lifetime' => 3600,
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
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
$userActivity = 'User visited Facility Request Page';
logActivity($username, $userActivity);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="../css/faciBook.css" rel="stylesheet" />
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' /> 
  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  <!-- FullCalendar CSS -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <title>Facility Booking - Calendar View</title>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #6d5dfc, #1493ff);
      color: #333;
      min-height: 100vh;
      padding-top: 70px;
      padding-bottom: 20px;
    }
    .container {
      max-width: 1200px;
      margin: auto;
    }
    /* Header Section for Facility Filters */
    .header-section {
      background: #fff;
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .checkbox-container label {
      margin-right: 15px;
      font-weight: 500;
    }
    /* Calendar Container */
    #calendar {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      min-height: 600px; /* Ensure the container is visible */
    }
  </style>
  <!-- FullCalendar JS -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
  <script>
    // Global facility data object (populated by PHP)
    let facilityData = {};

    // Utility: Convert a date string (e.g. "January 15, 2021") to ISO (YYYY-MM-DD)
    function convertToISO(dateStr) {
      let d = new Date(dateStr);
      if (!isNaN(d)) {
        return d.toISOString().split('T')[0];
      }
      return dateStr;
    }

    let calendar; // FullCalendar instance

    // Render calendar with given events
    function renderCalendar(events) {
      const calendarEl = document.getElementById('calendar');
      // If a calendar already exists, destroy it before re-rendering
      if (calendar) {
        calendar.destroy();
      }
      calendar = new FullCalendar.Calendar(calendarEl, {
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
    }

    // Update the calendar based on selected facilities
    function updateCalendar() {
      const checkboxes = document.querySelectorAll('input[name="facility"]:checked');
      const selectedFacilities = Array.from(checkboxes).map(cb => cb.value);
      let events = [];
      selectedFacilities.forEach(facility => {
        if (facilityData[facility]) {
          let facilityName = facilityData[facility].name;
          // Add blocked dates (red)
          if (facilityData[facility].blocked && facilityData[facility].blocked.length > 0) {
            facilityData[facility].blocked.forEach(dateStr => {
              events.push({
                title: facilityName + " (Blocked)",
                start: convertToISO(dateStr),
                color: '#dc3545'
              });
            });
          }
          // Add booked dates (amber)
          if (facilityData[facility].unavailable && facilityData[facility].unavailable.length > 0) {
            facilityData[facility].unavailable.forEach(dateStr => {
              events.push({
                title: facilityName + " (Booked)",
                start: convertToISO(dateStr),
                color: '#ffc107'
              });
            });
          }
        }
      });
      renderCalendar(events);
      logActivity('User updated calendar with facilities: ' + selectedFacilities.join(', '));
    }

    // Basic logging function via AJAX
    function logActivity(activity) {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "../system_log/log_activity.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.send("activity=" + encodeURIComponent(activity));
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Render an initial empty calendar
      renderCalendar([]);
    });
  </script>
</head>
<body>
  <header>
    <?php include '../includes/clientnavbar.php'; ?>
  </header>

  <div class="container">
    <!-- Facility Selection Header -->
    <div class="header-section">
      <h3>Select Facilities to Filter Dates</h3>
      <div class="checkbox-container">
        <?php
          $conn = getDbConnection(); // Using your included database connection
          $sql = "SELECT 
                    f.code, 
                    f.name, 
                    GROUP_CONCAT(CASE WHEN fa.status = 'blocked' THEN DATE_FORMAT(fa.date, '%M %d, %Y') END) AS blocked,
                    GROUP_CONCAT(CASE WHEN fa.status = 'unavailable' THEN DATE_FORMAT(fa.date, '%M %d, %Y') END) AS unavailable
                  FROM facilities f
                  LEFT JOIN facility_availability fa ON f.id = fa.facility_id
                  GROUP BY f.id";
          $result = $conn->query($sql);
          if ($result->num_rows > 0) {
            $facilities = [];
            while ($row = $result->fetch_assoc()) {
              $facilities[$row['code']] = [
                'name' => $row['name'],
                'blocked' => $row['blocked'] ? explode(',', $row['blocked']) : [],
                'unavailable' => $row['unavailable'] ? explode(',', $row['unavailable']) : []
              ];
              echo '<label class="mr-3"><input type="checkbox" name="facility" value="' . htmlspecialchars($row['code']) . '" onchange="updateCalendar()"> ' . htmlspecialchars($row['name']) . '</label>';
            }
            echo '<script>facilityData = ' . json_encode($facilities) . ';</script>';
          } else {
            echo '<p>No facilities available.</p>';
          }
        ?>
      </div>
    </div>

    <!-- Full Calendar Container -->
    <div id="calendar"></div>
  </div>

  <footer>
    <?php include '../includes/footer.php'; ?>
  </footer>

  <!-- Bootstrap 4 JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>

<?php
session_start();
require_once '../database.php'; // Make sure this file creates a valid $conn connection

// Check Database Connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// -------------------------
// Handle POST Requests
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // ---------- Facility CREATE ----------
    if ($action === 'create') {
      $name        = $_POST['name'] ?? '';
      $code        = $_POST['code'] ?? '';
      $description = $_POST['description'] ?? '';

      $stmt = $conn->prepare("INSERT INTO facilities (name, code, description) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $name, $code, $description);

      if ($stmt->execute()) {
        $_SESSION['message'] = "Facility added successfully!";
      } else {
        $_SESSION['message'] = "Error adding facility: " . $stmt->error;
      }
      $stmt->close();

      header("Location: facilitymanagement.php");
      exit;
    }

    // ---------- Facility UPDATE ----------
    if ($action === 'update') {
      $id          = $_POST['id'] ?? '';
      $name        = $_POST['name'] ?? '';
      $code        = $_POST['code'] ?? '';
      $description = $_POST['description'] ?? '';

      if (!empty($id) && is_numeric($id)) {
        $stmt = $conn->prepare("UPDATE facilities SET name=?, code=?, description=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $code, $description, $id);

        if ($stmt->execute()) {
          $_SESSION['message'] = "Facility updated successfully!";
        } else {
          $_SESSION['message'] = "Error updating facility: " . $stmt->error;
        }
        $stmt->close();
      }

      header("Location: facilitymanagement.php");
      exit;
    }

    // ---------- Room ADD ----------
    if ($action === 'add_room') {
      $facility_id = $_POST['facility_id'] ?? '';
      $room_number = $_POST['room_number'] ?? '';
      $capacity    = $_POST['capacity'] ?? 30;
      $description = $_POST['description'] ?? '';

      if (!empty($facility_id) && !empty($room_number)) {
        $stmt = $conn->prepare("INSERT INTO rooms (facility_id, room_number, capacity, description) VALUES (?, ?, ?, ?)");
        // Assuming facility_id and capacity are integers and room_number & description are strings
        $stmt->bind_param("isis", $facility_id, $room_number, $capacity, $description);

        if ($stmt->execute()) {
          $_SESSION['message'] = "Room added successfully!";
        } else {
          $_SESSION['message'] = "Error adding room: " . $stmt->error;
        }
        $stmt->close();
      }

      header("Location: facilitymanagement.php");
      exit;
    }

    // ---------- Room EDIT ----------
    if ($action === 'edit_room') {
      $room_id     = $_POST['room_id'] ?? '';
      $room_number = $_POST['room_number'] ?? '';
      $capacity    = $_POST['capacity'] ?? 30;
      $description = $_POST['description'] ?? '';

      if (!empty($room_id)) {
        $stmt = $conn->prepare("UPDATE rooms SET room_number=?, capacity=?, description=? WHERE id=?");
        $stmt->bind_param("sisi", $room_number, $capacity, $description, $room_id);

        if ($stmt->execute()) {
          $_SESSION['message'] = "Room updated successfully!";
        } else {
          $_SESSION['message'] = "Error updating room: " . $stmt->error;
        }
        $stmt->close();
      }

      header("Location: facilitymanagement.php");
      exit;
    }
  }
}

// -------------------------
// Handle GET Requests for DELETE actions
// -------------------------
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  if (!empty($id) && is_numeric($id)) {
    $stmt = $conn->prepare("DELETE FROM facilities WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
      $_SESSION['message'] = "Facility deleted successfully!";
    } else {
      $_SESSION['message'] = "Error deleting facility: " . $stmt->error;
    }
    $stmt->close();
  }
  header("Location: facilitymanagement.php");
  exit;
}

if (isset($_GET['delete_room'])) {
  $room_id = $_GET['delete_room'];
  if (!empty($room_id)) {
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);

    if ($stmt->execute()) {
      $_SESSION['message'] = "Room deleted successfully!";
    } else {
      $_SESSION['message'] = "Error deleting room: " . $stmt->error;
    }
    $stmt->close();
  }
  header("Location: facilitymanagement.php");
  exit;
}

// -------------------------
// Pagination & Search Setup for Facilities
// -------------------------
$limit       = 10; // facilities per page
$page        = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page        = max($page, 1);
$start       = ($page - 1) * $limit;
$searchParam = isset($_GET['search']) ? trim($_GET['search']) : '';

$searchSQL = '';
if ($searchParam !== '') {
  // Use real_escape_string to avoid SQL injection when not using prepared statements
  $escSearch = $conn->real_escape_string($searchParam);
  $searchSQL = "WHERE (name LIKE '%$escSearch%' OR code LIKE '%$escSearch%')";
}

$countSql = "SELECT COUNT(*) AS total FROM facilities $searchSQL";
$countRes = $conn->query($countSql);
$total    = $countRes ? $countRes->fetch_assoc()['total'] : 0;
$countRes->free();
$totalPages = ($total > 0) ? ceil($total / $limit) : 1;

$sql = "SELECT * FROM facilities $searchSQL ORDER BY id DESC LIMIT $start, $limit";
$facilitiesResult = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Facility Management</title>
  <!-- Google Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    /* Global Styles */
    body {
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #6d5dfc, #1493ff);
      color: #333;
      padding-bottom: 60px;
    }

    a {
      text-decoration: none;
    }

    .container {
      max-width: 1200px;
      margin: auto;
    }

    /* Navbar */
    .navbar {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
      font-weight: 600;
      letter-spacing: 1px;
    }

    /* Card */
    .card {
      border-radius: 20px;
      border: none;
      margin-top: 30px;
      background-color: #fff;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      border-top-left-radius: 20px;
      border-top-right-radius: 20px;
      background-color: #f8f9fa;
      padding: 20px;
    }

    /* Buttons & Inputs */
    .btn-custom {
      border-radius: 20px;
      transition: all 0.3s;
    }

    .btn-custom:hover {
      transform: scale(1.02);
    }

    .search-bar {
      max-width: 260px;
    }

    .search-bar .form-control {
      border-top-left-radius: 30px;
      border-bottom-left-radius: 30px;
    }

    .search-bar .btn {
      border-top-right-radius: 30px;
      border-bottom-right-radius: 30px;
    }

    /* Table Styles */
    .table thead th {
      background-color: #343a40;
      color: #fff;
      border-top: none;
      padding: 12px;
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background-color: rgba(0, 0, 0, 0.03);
    }

    .table-hover tbody tr:hover {
      background-color: rgba(0, 0, 0, 0.05);
      transition: background-color 0.3s;
    }

    /* Pagination */
    .pagination .page-item .page-link {
      border-radius: 50px;
      margin: 0 3px;
      transition: background-color 0.3s;
    }

    .pagination .page-item .page-link:hover {
      background-color: #e2e6ea;
    }

    /* Modals */
    .modal-content {
      border-radius: 15px;
    }

    .modal-header {
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      background-color: #f8f9fa;
    }

    .modal-footer {
      border-bottom-left-radius: 15px;
      border-bottom-right-radius: 15px;
    }

    /* Back to Top Button */
    #backToTop {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 100;
      display: none;
    }
  </style>
</head>

<body>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Facility Management</a>
    </div>
  </nav>

  <div class="container mt-4">
    <div class="card shadow">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h4 class="fw-bold m-0">Manage Facilities</h4>
        <!-- Search & Add Facility -->
        <form class="d-flex align-items-center" method="GET" action="facilitymanagement.php">
          <div class="input-group search-bar me-2">
            <input type="text" class="form-control" name="search" placeholder="Search facility..." value="<?php echo htmlspecialchars($searchParam); ?>">
            <button class="btn btn-outline-secondary btn-custom" type="submit">
              <i class="bi bi-search"></i>
            </button>
          </div>
          <button type="button" class="btn btn-success btn-sm btn-custom" data-bs-toggle="modal" data-bs-target="#addFacilityModal">
            <i class="bi bi-plus-lg"></i> Add Facility
          </button>
        </form>
      </div>

      <div class="card-body">
        <!-- Session Message -->
        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-info alert-dismissible fade show text-center" role="alert">
            <?php echo $_SESSION['message'];
            unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <!-- Facilities List -->
        <div class="accordion" id="facilitiesAccordion">
          <?php while ($facility = $facilitiesResult->fetch_assoc()): ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?php echo $facility['id']; ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $facility['id']; ?>">
                  <?php echo htmlspecialchars($facility['name']); ?>
                </button>
              </h2>
              <div id="collapse<?php echo $facility['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#facilitiesAccordion">
                <div class="accordion-body">
                  <p><strong>Code:</strong> <?php echo htmlspecialchars($facility['code']); ?></p>
                  <p><?php echo htmlspecialchars($facility['description']); ?></p>
                  <!-- Facility Edit & Delete -->
                  <div class="mb-3">
                    <button class="btn btn-warning btn-sm btn-custom" data-bs-toggle="modal" data-bs-target="#editFacilityModal"
                      data-facility-id="<?php echo $facility['id']; ?>"
                      data-facility-name="<?php echo htmlspecialchars($facility['name']); ?>"
                      data-facility-code="<?php echo htmlspecialchars($facility['code']); ?>"
                      data-facility-description="<?php echo htmlspecialchars($facility['description']); ?>">
                      <i class="bi bi-pencil-square"></i> Edit Facility
                    </button>
                    <a href="facilitymanagement.php?delete=<?php echo $facility['id']; ?>" class="btn btn-danger btn-sm btn-custom"
                      onclick="return confirm('Are you sure you want to delete this facility?')">
                      <i class="bi bi-trash"></i> Delete Facility
                    </a>
                  </div>

                  <!-- Room Section -->
                  <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#addRoomModal"
                    data-facility-id="<?php echo $facility['id']; ?>">
                    <i class="bi bi-plus-lg"></i> Add Room
                  </button>
                  <ul class="list-group">
                    <?php
                    $facility_id = $facility['id'];
                    $roomsResult = $conn->query("SELECT * FROM rooms WHERE facility_id = $facility_id ORDER BY id DESC");
                    while ($room = $roomsResult->fetch_assoc()):
                    ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                        Room <?php echo htmlspecialchars($room['room_number']); ?> (<?php echo htmlspecialchars($room['capacity']); ?> seats)
                        <div>
                          <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editRoomModal"
                            data-room-id="<?php echo $room['id']; ?>"
                            data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>"
                            data-room-capacity="<?php echo htmlspecialchars($room['capacity']); ?>"
                            data-room-description="<?php echo htmlspecialchars($room['description']); ?>">
                            <i class="bi bi-pencil-square"></i> Edit
                          </button>
                          <a href="facilitymanagement.php?delete_room=<?php echo $room['id']; ?>" class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this room?')">
                            <i class="bi bi-trash"></i> Delete
                          </a>
                        </div>
                      </li>
                    <?php endwhile; ?>
                  </ul>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Pagination Controls -->
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center mt-3">
            <li class="page-item <?php echo ($page <= 1 ? 'disabled' : ''); ?>">
              <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchParam); ?>">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($page == $i ? 'active' : ''); ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchParam); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($page >= $totalPages ? 'disabled' : ''); ?>">
              <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchParam); ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>

  <!-- Back to Top Button -->
  <button id="backToTop" class="btn btn-primary btn-custom"><i class="bi bi-arrow-up"></i></button>

  <!-- ADD FACILITY MODAL -->
  <div class="modal fade" id="addFacilityModal" tabindex="-1" aria-labelledby="addFacilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="facilitymanagement.php" method="POST">
          <input type="hidden" name="action" value="create">
          <div class="modal-header">
            <h5 class="modal-title" id="addFacilityModalLabel">Add New Facility</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="facilityName" class="form-label">Facility Name</label>
              <input type="text" class="form-control" id="facilityName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="facilityCode" class="form-label">Code</label>
              <input type="text" class="form-control" id="facilityCode" name="code" required>
            </div>
            <div class="mb-3">
              <label for="facilityDescription" class="form-label">Description</label>
              <textarea class="form-control" id="facilityDescription" name="description" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success btn-custom">Save Facility</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- EDIT FACILITY MODAL -->
  <div class="modal fade" id="editFacilityModal" tabindex="-1" aria-labelledby="editFacilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="facilitymanagement.php" method="POST">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" id="editFacilityId">
          <div class="modal-header">
            <h5 class="modal-title" id="editFacilityModalLabel">Edit Facility</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="editFacilityName" class="form-label">Facility Name</label>
              <input type="text" class="form-control" id="editFacilityName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="editFacilityCode" class="form-label">Code</label>
              <input type="text" class="form-control" id="editFacilityCode" name="code" required>
            </div>
            <div class="mb-3">
              <label for="editFacilityDescription" class="form-label">Description</label>
              <textarea class="form-control" id="editFacilityDescription" name="description" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning btn-custom">Update Facility</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ADD ROOM MODAL -->
  <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="facilitymanagement.php" method="POST">
          <input type="hidden" name="action" value="add_room">
          <input type="hidden" name="facility_id" id="addRoomFacilityId">
          <div class="modal-header">
            <h5 class="modal-title" id="addRoomModalLabel">Add New Room</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="roomNumber" class="form-label">Room Number</label>
              <input type="text" class="form-control" id="roomNumber" name="room_number" required>
            </div>
            <div class="mb-3">
              <label for="roomCapacity" class="form-label">Capacity</label>
              <input type="number" class="form-control" id="roomCapacity" name="capacity" value="30" required>
            </div>
            <div class="mb-3">
              <label for="roomDescription" class="form-label">Description</label>
              <textarea class="form-control" id="roomDescription" name="description" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success btn-custom">Save Room</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- EDIT ROOM MODAL -->
  <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="facilitymanagement.php" method="POST">
          <input type="hidden" name="action" value="edit_room">
          <input type="hidden" name="room_id" id="editRoomId">
          <div class="modal-header">
            <h5 class="modal-title" id="editRoomModalLabel">Edit Room</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="editRoomNumber" class="form-label">Room Number</label>
              <input type="text" class="form-control" id="editRoomNumber" name="room_number" required>
            </div>
            <div class="mb-3">
              <label for="editRoomCapacity" class="form-label">Capacity</label>
              <input type="number" class="form-control" id="editRoomCapacity" name="capacity" required>
            </div>
            <div class="mb-3">
              <label for="editRoomDescription" class="form-label">Description</label>
              <textarea class="form-control" id="editRoomDescription" name="description" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-custom" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning btn-custom">Update Room</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS (for modals) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Auto-fill data into Edit Facility Modal
    var editFacilityModal = document.getElementById('editFacilityModal');
    editFacilityModal.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;
      document.getElementById('editFacilityId').value = button.getAttribute('data-facility-id');
      document.getElementById('editFacilityName').value = button.getAttribute('data-facility-name');
      document.getElementById('editFacilityCode').value = button.getAttribute('data-facility-code');
      document.getElementById('editFacilityDescription').value = button.getAttribute('data-facility-description');
    });

    // Set Facility ID for Add Room Modal
    var addRoomModal = document.getElementById('addRoomModal');
    addRoomModal.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;
      var facilityId = button.getAttribute('data-facility-id');
      document.getElementById('addRoomFacilityId').value = facilityId;
    });

    // Auto-fill data into Edit Room Modal
    var editRoomModal = document.getElementById('editRoomModal');
    editRoomModal.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;
      document.getElementById('editRoomId').value = button.getAttribute('data-room-id');
      document.getElementById('editRoomNumber').value = button.getAttribute('data-room-number');
      document.getElementById('editRoomCapacity').value = button.getAttribute('data-room-capacity');
      document.getElementById('editRoomDescription').value = button.getAttribute('data-room-description');
    });

    // Back to Top Button
    var backToTopBtn = document.getElementById('backToTop');
    window.onscroll = function() {
      backToTopBtn.style.display = (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) ? "block" : "none";
    };
    backToTopBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  </script>
</body>

</html>
<?php
$conn->close();
?>
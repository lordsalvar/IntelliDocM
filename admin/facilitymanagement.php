<?php
session_start();
require_once '../database.php';

// Check Database Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -------------------------
// Handle CREATE (Add)
// -------------------------
if (isset($_POST['action']) && $_POST['action'] === 'create') {
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

// -------------------------
// Handle UPDATE (Edit)
// -------------------------
if (isset($_POST['action']) && $_POST['action'] === 'update') {
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

// -------------------------
// Handle DELETE
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

// -------------------------
// Pagination & Search Setup
// -------------------------
$limit     = 10; // Facilities per page
$page      = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page      = max($page, 1);
$start     = ($page - 1) * $limit;
$searchParam = isset($_GET['search']) ? trim($_GET['search']) : '';

$searchSQL = '';
if ($searchParam !== '') {
    $searchSQL = "WHERE (name LIKE '%$searchParam%' OR code LIKE '%$searchParam%')";
}

// Count total records (with optional search)
$countSql = "SELECT COUNT(*) AS total FROM facilities $searchSQL";
$countRes = $conn->query($countSql);
$total    = $countRes ? $countRes->fetch_assoc()['total'] : 0;
$countRes->free();
$totalPages = ($total > 0) ? ceil($total / $limit) : 1;

// Retrieve facilities (with optional search)
$sql    = "SELECT * FROM facilities $searchSQL ORDER BY id DESC LIMIT $start, $limit";
$result = $conn->query($sql);
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
        }
        a {
            text-decoration: none;
        }
        /* Navbar */
        .navbar {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .card-header {
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            background-color: #f8f9fa;
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
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.03);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.05);
            transition: background-color 0.3s;
        }
        /* Pagination */
        .pagination .page-item .page-link {
            border-radius: 50px;
            margin: 0 3px;
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
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Facility Management</a>
    </div>
</nav>

<div class="container">
    <!-- CARD WRAPPER -->
    <div class="card shadow">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="fw-bold m-0">Manage Facilities</h4>
            <!-- Search & Add -->
            <form class="d-flex align-items-center" method="GET" action="facilitymanagement.php">
                <div class="input-group search-bar me-2">
                    <input type="text" class="form-control" name="search" placeholder="Search facility..." value="<?php echo htmlspecialchars($searchParam); ?>">
                    <button class="btn btn-outline-secondary btn-custom" type="submit"><i class="bi bi-search"></i></button>
                </div>
                <button type="button" class="btn btn-success btn-sm btn-custom" data-bs-toggle="modal" data-bs-target="#addFacilityModal">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </form>
        </div>

        <div class="card-body">
            <!-- Session Message -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-info alert-dismissible fade show text-center" role="alert">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Facilities Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px;">ID</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Description</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center"><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['code']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm btn-custom me-1" data-bs-toggle="modal" data-bs-target="#editFacilityModal" 
                                        data-facility-id="<?php echo $row['id']; ?>"
                                        data-facility-name="<?php echo htmlspecialchars($row['name']); ?>"
                                        data-facility-code="<?php echo htmlspecialchars($row['code']); ?>"
                                        data-facility-description="<?php echo htmlspecialchars($row['description']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="facilitymanagement.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-custom"
                                       onclick="return confirm('Are you sure you want to delete this facility?');">
                                       <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No facilities found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-3">
                    <li class="page-item <?php echo ($page <= 1 ? 'disabled' : ''); ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchParam); ?>" aria-label="Previous">
                            &laquo;
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i ? 'active' : ''); ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchParam); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPages ? 'disabled' : ''); ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchParam); ?>" aria-label="Next">
                            &raquo;
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

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
          <button type="submit" class="btn btn-warning btn-custom">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS (for modals) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-fill data into Edit Facility Modal
    const editModal = document.getElementById('editFacilityModal');
    editModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      document.getElementById('editFacilityId').value = button.getAttribute('data-facility-id');
      document.getElementById('editFacilityName').value = button.getAttribute('data-facility-name');
      document.getElementById('editFacilityCode').value = button.getAttribute('data-facility-code');
      document.getElementById('editFacilityDescription').value = button.getAttribute('data-facility-description');
    });
</script>
</body>
</html>
<?php
$conn->close();
?>

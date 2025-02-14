<?php
require_once '../database.php'; // Adjust the path as necessary

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_facility'])) {
        $facilityId = $_POST['facility_id'];
        $stmt = $conn->prepare("DELETE FROM facilities WHERE id = ?");
        $stmt->bind_param("i", $facilityId);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Facility deleted successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } elseif (isset($_POST['edit_facility'])) {
        $facilityId = $_POST['facility_id'];
        $facilityName = $_POST['facility_name'];
        $facilityDescription = $_POST['facility_description'];
        $stmt = $conn->prepare("UPDATE facilities SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $facilityName, $facilityDescription, $facilityId);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Facility updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $facilityName = $_POST['facility_name'];
        $facilityDescription = $_POST['facility_description'];
        $facilityCode = uniqid('FAC_'); // Generate a unique code for the facility

        $stmt = $conn->prepare("INSERT INTO facilities (name, code, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $facilityName, $facilityCode, $facilityDescription);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Facility added: " . htmlspecialchars($facilityName) . "</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
}

// Fetch all facilities from the database
$facilities = [];
$result = $conn->query("SELECT * FROM facilities");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Facility Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Facility Management</h1>
        <?php if (isset($message)) echo $message; ?>
        <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addFacilityModal">Add Facility</button>

        <h2>All Facilities</h2>
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facilities as $facility): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($facility['id']); ?></td>
                        <td><?php echo htmlspecialchars($facility['name']); ?></td>
                        <td><?php echo htmlspecialchars($facility['code']); ?></td>
                        <td><?php echo htmlspecialchars($facility['description']); ?></td>
                        <td>
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="facility_id" value="<?php echo $facility['id']; ?>">
                                <button type="submit" name="delete_facility" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            <button class="btn btn-warning btn-sm" onclick="editFacility(<?php echo $facility['id']; ?>, '<?php echo htmlspecialchars($facility['name']); ?>', '<?php echo htmlspecialchars($facility['description']); ?>')">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Facility Modal -->
    <div class="modal fade" id="addFacilityModal" tabindex="-1" role="dialog" aria-labelledby="addFacilityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addFacilityModalLabel">Add Facility</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="facility_name">Facility Name:</label>
                            <input type="text" class="form-control" id="facility_name" name="facility_name" required>
                        </div>
                        <div class="form-group">
                            <label for="facility_description">Facility Description:</label>
                            <textarea class="form-control" id="facility_description" name="facility_description" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Facility</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Facility Modal -->
    <div class="modal fade" id="editFacilityModal" tabindex="-1" role="dialog" aria-labelledby="editFacilityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editFacilityModalLabel">Edit Facility</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_facility_id" name="facility_id">
                        <div class="form-group">
                            <label for="edit_facility_name">Facility Name:</label>
                            <input type="text" class="form-control" id="edit_facility_name" name="facility_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_facility_description">Facility Description:</label>
                            <textarea class="form-control" id="edit_facility_description" name="facility_description" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="edit_facility" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function editFacility(id, name, description) {
            $('#edit_facility_id').val(id);
            $('#edit_facility_name').val(name);
            $('#edit_facility_description').val(description);
            $('#editFacilityModal').modal('show');
        }
    </script>
</body>
</html>

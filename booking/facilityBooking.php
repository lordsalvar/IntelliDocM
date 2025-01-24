<?php
session_start([
    'cookie_lifetime' => 3600, // Session expires after 1 hour
    'cookie_httponly' => true, // Prevent JavaScript access
    'cookie_secure' => isset($_SERVER['HTTPS']), // HTTPS only
    'use_strict_mode' => true // Strict session handling
]);

// Prevent session fixation
session_regenerate_id(true);

// Check user role
if ($_SESSION['role'] !== 'client') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
include '../database.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/faciBook.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <title>Facility Booking</title>
    <script>
        let facilityData = {};

        function showDates() {
            const checkboxes = document.querySelectorAll('input[name="facility"]:checked');
            const selectedFacilities = Array.from(checkboxes).map(checkbox => checkbox.value);
            const modalContent = document.getElementById("modalContent");

            if (selectedFacilities.length === 0) {
                modalContent.innerHTML = "<p>Please select at least one facility.</p>";
                openModal();
                return;
            }

            let output = "";
            selectedFacilities.forEach(facility => {
                if (facilityData[facility]) {
                    output += `<h4>${facilityData[facility].name}</h4>`;
                    if (facilityData[facility].blocked.length > 0) {
                        output += `<p><strong>Blocked Dates:</strong> ${facilityData[facility].blocked.join(', ')}</p>`;
                    }
                    if (facilityData[facility].unavailable.length > 0) {
                        output += `<p><strong>Unavailable Dates:</strong> ${facilityData[facility].unavailable.join(', ')}</p>`;
                    }
                }
            });

            modalContent.innerHTML = output || "<p>No blocked or unavailable dates found for the selected facilities.</p>";
            openModal();
        }

        function openModal() {
            document.getElementById("myModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("myModal").style.display = "none";
        }
    </script>
</head>

<body>
<div class="page-wrapper">
    <div class="container">
        <h3>Select Facilities</h3>
        <div class="checkbox-container">
            <?php
            $conn = getDbConnection(); // Use the global connection defined in your included file

            // Fetch facilities and their blocked/unavailable dates
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
                    echo '<label><input type="checkbox" name="facility" value="' . htmlspecialchars($row['code']) . '"> ' . htmlspecialchars($row['name']) . '</label>';
                }

                // Pass facility data to JavaScript
                echo '<script>facilityData = ' . json_encode($facilities) . ';</script>';
            } else {
                echo '<p>No facilities available.</p>';
            }
            ?>
        </div>
        <button onclick="showDates()" class="btn btn-info mt-3">Show Dates</button>
        <button onclick="location.href='../public/forms.php';" class="btn btn-success mt-3">Back to Forms</button>

        <!-- Button to trigger modal for Block Request Form -->
        <button type="button" class="btn btn-danger mt-3" data-toggle="modal" data-target="#blockRequestModal">
            Request Block Date
        </button>
    </div>

    <!-- Modal for Block Request Form -->
    <div class="modal fade" id="blockRequestModal" tabindex="-1" aria-labelledby="blockRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="blockRequestModalLabel">Block Request Form</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="blockRequestForm" method="POST" action="process_block_request.php">
                        <div class="form-group">
                            <label for="facility">Select Facility:</label>
                            <select name="facility" id="facility" class="form-control" required>
                                <?php
                                $facilitiesQuery = "SELECT id, name FROM facilities";
                                $facilitiesResult = $conn->query($facilitiesQuery);
                                while ($row = $facilitiesResult->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date">Select Date:</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success">Submit Block Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Facility Dates -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="modalContent">
                <p>Loading...</p>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>

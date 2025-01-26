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
    <header>
        <?php include '../includes/clientnavbar.php'; ?>
    </header>

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
                            <div id="venueDateContainer">
                                <!-- Venue and Date Pair -->
                                <div class="venue-date-pair mb-3" id="venue-date-pair-1">
                                    <div class="form-group">
                                        <label for="facility1">Select Facility:</label>
                                        <select name="facilities[]" id="facility1" class="form-control" required>
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
                                        <label for="date1">Select Date:</label>
                                        <input type="date" name="dates[]" id="date1" class="form-control" required>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeVenueDate(1)">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mb-3" onclick="addVenueDate()">Add Another Venue</button>
                            <button type="submit" class="btn btn-success w-100">Submit Block Request</button>
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

    <!-- Success/Error Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultModalLabel"></h5>
                </div>
                <div class="modal-body" id="resultModalBody"></div>
                <div class="modal-footer">
                </div>
            </div>
        </div>
    </div>

    <footer>
        <?php include '../includes/footer.php'; ?>
    </footer>


    <script>
        let venueCounter = 1;

        // Function to add a new venue-date pair
        function addVenueDate() {
            venueCounter++;

            const container = document.getElementById('venueDateContainer');
            const newPair = document.createElement('div');
            newPair.classList.add('venue-date-pair', 'mb-3');
            newPair.id = `venue-date-pair-${venueCounter}`;

            newPair.innerHTML = `
            <div class="form-group">
                <label for="facility${venueCounter}">Select Facility:</label>
                <select name="facilities[]" id="facility${venueCounter}" class="form-control" required>
                    <?php
                    $facilitiesResult->data_seek(0); // Reset the result set
                    while ($row = $facilitiesResult->fetch_assoc()) {
                        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date${venueCounter}">Select Date:</label>
                <input type="date" name="dates[]" id="date${venueCounter}" class="form-control" required>
            </div>
            <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeVenueDate(${venueCounter})">Remove</button>
        `;

            container.appendChild(newPair);
        }

        // Function to remove a specific venue-date pair
        function removeVenueDate(counter) {
            const pair = document.getElementById(`venue-date-pair-${counter}`);
            if (pair) {
                pair.remove();
            }
        }
    </script>


    <script>
        // Handle form submission via AJAX
        const form = document.getElementById('blockRequestForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json(); // Parse JSON response

                // Show the result modal
                showResultModal(result.title, result.message, result.success);

                if (result.success) {
                    let countdown = 5; // Countdown duration in seconds
                    const modalBody = document.getElementById('resultModalBody');

                    const interval = setInterval(() => {
                        countdown--;
                        modalBody.textContent = `${result.message} Redirecting in ${countdown} seconds...`;

                        if (countdown <= 0) {
                            clearInterval(interval);
                            window.location.href = '../activity_proposal_form.php';
                        }
                    }, 1000);
                }
            } catch (error) {
                showResultModal('Error', 'An unexpected error occurred. Please try again.', false);
            }
        });
    </script>
    <script>
        function showResultModal(title, message, isSuccess) {
            const modalTitle = document.getElementById('resultModalLabel');
            const modalBody = document.getElementById('resultModalBody');

            // Set title and message
            modalTitle.textContent = title;
            modalBody.textContent = message;

            // Change modal appearance based on success or error
            if (isSuccess) {
                modalTitle.classList.remove('text-danger');
                modalTitle.classList.add('text-success');
            } else {
                modalTitle.classList.remove('text-success');
                modalTitle.classList.add('text-danger');
            }

            // Show the modal
            const resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
            resultModal.show();
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
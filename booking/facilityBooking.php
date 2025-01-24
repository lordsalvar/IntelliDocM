<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/faciBook.css" rel="stylesheet" />
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
    <div class="container">
        <h3>Select Facilities</h3>
        <div class="checkbox-container">
            <?php
            include '../database.php'; // Include your existing database connection file

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
        <button onclick="showDates()">Show Dates</button>
        <!-- Back Button -->
        <button onclick="location.href='../public/forms.php';" class="back-button">Back to Forms</button>
    </div>

    <!-- Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="modalContent">
                <p>Loading...</p>
            </div>
        </div>
    </div>
</body>

</html>
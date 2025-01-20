<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/faciBook.css" rel="stylesheet" />
    <title>Facility Booking</title>
    <script>
        function showDates() {
            const checkboxes = document.querySelectorAll('input[name="facility"]:checked');
            const selectedFacilities = Array.from(checkboxes).map(checkbox => checkbox.value);
            const modalContent = document.getElementById("modalContent");

            if (selectedFacilities.length === 0) {
                modalContent.innerHTML = "<p>Please select at least one facility.</p>";
                openModal();
                return;
            }

            // Facility data and available dates
            const facilityData = {
                ladouix: "<h4>Ladouix Hall</h4><p>Available Dates: January 5, January 12, January 19</p>",
                boulay: "<h4>Boulay Bldg.</h4><p>Available Dates: February 3, February 10, February 17</p>",
                gym: "<h4>Gymnasium</h4><p>Available Dates: March 7, March 14, March 21</p>",
                misereor: "<h4>Misereor Bldg.</h4><p>Available Dates: April 2, April 9, April 16</p>",
                polycarp: "<h4>Polycarp Bldg.</h4><p>Available Dates: May 4, May 11, May 18</p>",
                coinindre: "<h4>Coinindre Bldg.</h4><p>Available Dates: June 6, June 13, June 20</p>",
                piazza: "<h4>Piazza</h4><p>Available Dates: July 8, July 15, July 22</p>",
                xavier: "<h4>Xavier Hall</h4><p>Available Dates: August 1, August 8, August 15</p>",
                openCourt: "<h4>Open Court w/ Lights</h4><p>Available Dates: September 3, September 10, September 17</p>",
                ivet: "<h4>IVET</h4><p>Available Dates: October 5, October 12, October 19</p>",
                nursing: "<h4>Nursing Room/Hall</h4><p>Available Dates: November 7, November 14, November 21</p>",
                coindre: "<h4>Coindre Bldg. (CR)</h4><p>Available Dates: December 2, December 9, December 16</p>",
                powerCampus: "<h4>Power Campus</h4><p>Available Dates: January 3, January 10, January 17</p>",
                campRaymond: "<h4>Camp Raymond Bldg.</h4><p>Available Dates: February 5, February 12, February 19</p>",
                norbert: "<h4>Norbert Bldg.</h4><p>Available Dates: March 3, March 10, March 17</p>",
                hehall: "<h4>H.E Hall</h4><p>Available Dates: April 4, April 11, April 18</p>",
                atrium: "<h4>Atrium</h4><p>Available Dates: May 5, May 12, May 19</p>",
            };

            // Build the HTML for selected facilities
            let output = "";
            selectedFacilities.forEach(facility => {
                if (facilityData[facility]) {
                    output += facilityData[facility];
                }
            });

            modalContent.innerHTML = output || "<p>No available dates found for the selected facilities.</p>";
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
            <!-- Facility checkboxes -->
            <label><input type="checkbox" name="facility" value="ladouix"> Ladouix Hall</label>
            <label><input type="checkbox" name="facility" value="boulay"> Boulay Bldg.</label>
            <label><input type="checkbox" name="facility" value="gym"> Gymnasium</label>
            <label><input type="checkbox" name="facility" value="misereor"> Misereor Bldg.</label>
            <label><input type="checkbox" name="facility" value="polycarp"> Polycarp Bldg.</label>
            <label><input type="checkbox" name="facility" value="coinindre"> Coinindre Bldg.</label>
            <label><input type="checkbox" name="facility" value="piazza"> Piazza</label>
            <label><input type="checkbox" name="facility" value="xavier"> Xavier Hall</label>
            <label><input type="checkbox" name="facility" value="openCourt"> Open Court w/ Lights</label>
            <label><input type="checkbox" name="facility" value="ivet"> IVET</label>
            <label><input type="checkbox" name="facility" value="nursing"> Nursing Room/Hall</label>
            <label><input type="checkbox" name="facility" value="coindre"> Coindre Bldg. (CR)</label>
            <label><input type="checkbox" name="facility" value="powerCampus"> Power Campus</label>
            <label><input type="checkbox" name="facility" value="campRaymond"> Camp Raymond Bldg.</label>
            <label><input type="checkbox" name="facility" value="norbert"> Norbert Bldg.</label>
            <label><input type="checkbox" name="facility" value="hehall"> H.E Hall</label>
            <label><input type="checkbox" name="facility" value="atrium"> Atrium</label>
        </div>
        <button onclick="showDates()">Show Available Dates</button>
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

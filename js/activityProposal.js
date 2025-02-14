document.addEventListener("DOMContentLoaded", function() {
    // *********************************************
    // 1. Function to Log Form Submission via AJAX
    // *********************************************
    function logSubmitProposal() {
        const userActivity = 'User submitted the Activity Proposal Form';
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "system_log/log_activity.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                console.log('Activity logged successfully.');
            }
        };
        xhr.send("activity=" + encodeURIComponent(userActivity));
    }

    // *********************************************
    // 2. Form Submission: Log activity then submit
    // *********************************************
    const proposalForm = document.getElementById('submitProposalForm');
    proposalForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default submit action
        logSubmitProposal();
        proposalForm.submit(); // Submit the form after logging
    });

    // *********************************************
    // 3. Dynamic Facility Booking Blocks & Time Slots
    // *********************************************
    let bookingIndex = 0;

    // When the "Add Booking" button is clicked:
    document.getElementById("addBooking").addEventListener("click", function() {
        bookingIndex++;
        const container = document.getElementById("facilityBookingsContainer");
        const blockDiv = document.createElement("div");
        blockDiv.classList.add("card", "mb-3", "facility-booking");
        blockDiv.dataset.index = bookingIndex;

        // Build the booking block HTML using a template literal.
        blockDiv.innerHTML = `
            <div class="card-body">
                <h5 class="card-title">Facility Booking #<span class="booking-number">${bookingIndex + 1}</span></h5>
                <div class="mb-3">
                    <label for="facilitySelect_${bookingIndex}" class="form-label fw-bold">Select Facility:</label>
                    <select class="form-select facility-select" id="facilitySelect_${bookingIndex}" name="facilityBookings[${bookingIndex}][facility]" data-index="${bookingIndex}">
                        <option value="">-- Select Facility --</option>
                        ${generateFacilityOptions()}
                    </select>
                </div>
                <div class="time-slots" data-index="${bookingIndex}">
                    <div class="row g-2 align-items-end time-slot" data-index="0">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Date:</label>
                            <input type="date" class="form-control" name="facilityBookings[${bookingIndex}][slots][0][date]">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Room or Building</label>
                            <input type="text" class="form-control room-or-building" name="facilityBookings[${bookingIndex}][slots][0][room]">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Start Time:</label>
                            <input type="time" class="form-control" name="facilityBookings[${bookingIndex}][slots][0][start]">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">End Time:</label>
                            <input type="time" class="form-control" name="facilityBookings[${bookingIndex}][slots][0][end]">
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="button" class="addSlot btn btn-secondary btn-sm mt-4" data-index="${bookingIndex}" title="Add Time Slot">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="removeSlot btn btn-danger btn-sm mt-4" title="Remove Slot">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="button" class="removeBooking btn btn-outline-danger btn-sm" title="Remove Facility Booking">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(blockDiv);
    });

    // Helper function to generate facility options
    function generateFacilityOptions() {
        let options = "";
        // "facilitiesData" is a global variable passed from PHP (see Step 3 below)
        for (let facilityId in facilitiesData) {
            if (facilitiesData.hasOwnProperty(facilityId)) {
                const facilityName = facilitiesData[facilityId].name;
                options += `<option value="${facilityId}">${facilityName}</option>`;
            }
        }
        return options;
    }

    // Remove a facility booking block when "Remove Booking" is clicked
    document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
        if (e.target && e.target.closest(".removeBooking")) {
            e.target.closest(".facility-booking").remove();
        }
    });

    // Add a new time slot within a facility booking block when "Add Slot" is clicked
    document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
        if (e.target && e.target.closest(".addSlot")) {
            const block = e.target.closest(".facility-booking");
            const bookingIdx = block.dataset.index;
            const timeSlotsContainer = block.querySelector(".time-slots");
            let slotIndex = timeSlotsContainer.querySelectorAll(".time-slot").length;

            const slotDiv = document.createElement("div");
            slotDiv.classList.add("row", "g-2", "align-items-end", "time-slot");
            slotDiv.dataset.index = slotIndex;

            slotDiv.innerHTML = `
                <div class="col-md-3">
                    <label class="form-label fw-bold">Date:</label>
                    <input type="date" class="form-control" name="facilityBookings[${bookingIdx}][slots][${slotIndex}][date]">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Room or Building</label>
                    <input type="text" class="form-control room-or-building" name="facilityBookings[${bookingIdx}][slots][${slotIndex}][room]">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Start Time:</label>
                    <input type="time" class="form-control" name="facilityBookings[${bookingIdx}][slots][${slotIndex}][start]">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">End Time:</label>
                    <input type="time" class="form-control" name="facilityBookings[${bookingIdx}][slots][${slotIndex}][end]">
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="removeSlot btn btn-danger btn-sm mt-4" title="Remove Slot">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            timeSlotsContainer.appendChild(slotDiv);
        }
    });

    // Remove a time slot when "Remove Slot" is clicked
    document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
        if (e.target && e.target.closest(".removeSlot")) {
            e.target.closest(".time-slot").remove();
        }
    });

    // *********************************************
    // 4. Update Room Input Based on Facility Selection
    // *********************************************
    document.getElementById("facilityBookingsContainer").addEventListener("change", function(e) {
        if (e.target && e.target.classList.contains("facility-select")) {
            const facilityId = e.target.value;
            const bookingIdx = e.target.dataset.index;
            const roomOrBuildingInput = document.querySelector(`.facility-booking[data-index="${bookingIdx}"] .room-or-building`);

            if (facilityId) {
                // If the facility has registered rooms, let the user choose one; otherwise, auto-fill the facility name.
                if (facilitiesData[facilityId].rooms && facilitiesData[facilityId].rooms.length > 0) {
                    roomOrBuildingInput.value = "";
                    roomOrBuildingInput.placeholder = "Select Room";
                    roomOrBuildingInput.readOnly = false;
                } else {
                    roomOrBuildingInput.value = facilitiesData[facilityId].name;
                    roomOrBuildingInput.placeholder = "";
                    roomOrBuildingInput.readOnly = true;
                }
            } else {
                roomOrBuildingInput.value = "";
                roomOrBuildingInput.placeholder = "Room or Building";
                roomOrBuildingInput.readOnly = false;
            }
        }
    });

    // *********************************************
    // 5. Toggle Venue & Address Fields Based on Activity Type
    // *********************************************
    const onCampusCheckbox = document.getElementById('on-campus');
    const offCampusCheckbox = document.getElementById('off-campus');
    const venueAddressContainer = document.getElementById('venue-address-container');

    function toggleVenueAddress() {
        if (onCampusCheckbox.checked || offCampusCheckbox.checked) {
            venueAddressContainer.style.display = 'flex';
        } else {
            venueAddressContainer.style.display = 'none';
        }
    }
    // Initially hide the venue/address container
    venueAddressContainer.style.display = 'none';
    onCampusCheckbox.addEventListener('change', toggleVenueAddress);
    offCampusCheckbox.addEventListener('change', toggleVenueAddress);

    // *********************************************
    // 6. Allow Only One Activity Type Checkbox to be Selected
    // *********************************************
    const activityTypeCheckboxes = document.querySelectorAll(".activity-type");
    activityTypeCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", function() {
            activityTypeCheckboxes.forEach((box) => {
                if (box !== this) {
                    box.checked = false;
                }
            });
        });
    });
});

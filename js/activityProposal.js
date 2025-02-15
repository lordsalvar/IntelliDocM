document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('submitProposalForm');
    if (!form) return;

    // Single form submission handler
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Show loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;
        
        const loadingContent = document.createElement('div');
        loadingContent.innerHTML = `
            <div class="text-center p-4 bg-white rounded shadow">
                <div class="spinner-border text-primary mb-3"></div>
                <div>Submitting proposal...</div>
            </div>
        `;
        
        loadingOverlay.appendChild(loadingContent);
        document.body.appendChild(loadingOverlay);

        // Log the activity then submit
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "system_log/log_activity.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            // After logging, submit the form
            form.submit();
        };
        xhr.send("activity=" + encodeURIComponent("User submitted the Activity Proposal Form"));
    });

    // ****************************************************************
    // 2) The rest of your code for dynamic booking blocks, conflict checks
    //    (on change), toggles for on-campus/off-campus, etc.
    // ****************************************************************

    let bookingIndex = 0;

    // "Add Booking" button
    document.getElementById("addBooking").addEventListener("click", function() {
        addBooking();
    });

    // Generate facility options
    function generateFacilityOptions() {
        let options = "";
        // "facilitiesData" is a global variable passed from PHP
        for (let facilityId in facilitiesData) {
            if (facilitiesData.hasOwnProperty(facilityId)) {
                const facilityName = facilitiesData[facilityId].name;
                options += `<option value="${facilityId}">${facilityName}</option>`;
            }
        }
        return options;
    }

    // Remove booking block
    document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
        if (e.target && e.target.closest(".removeBooking")) {
            e.target.closest(".facility-booking").remove();
        }
    });

    // Add new time slot
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

    // Remove time slot
    document.getElementById("facilityBookingsContainer").addEventListener("click", function(e) {
        if (e.target && e.target.closest(".removeSlot")) {
            e.target.closest(".time-slot").remove();
        }
    });

    // 4) Update room input based on selected facility
    document.getElementById("facilityBookingsContainer").addEventListener("change", function(e) {
        if (e.target && e.target.classList.contains("facility-select")) {
            const facilityId = e.target.value;
            const bookingIdx = e.target.dataset.index;
            const roomContainer = e.target.closest('.facility-booking').querySelector('.room-selection');
            
            if (facilityId && facilitiesData[facilityId]) {
                const facility = facilitiesData[facilityId];
                
                // Clear existing room options
                roomContainer.innerHTML = '';
                
                if (facility.rooms && facility.rooms.length > 0) {
                    // Create a room selection dropdown
                    const roomSelect = document.createElement('select');
                    roomSelect.className = 'form-select form-select-sm';
                    // Update the name attribute
                    roomSelect.name = `facilityBookings[${bookingIdx}][room]`;
                    
                    // Add default option
                    roomSelect.innerHTML = '<option value="">-- Select Room --</option>';
                    
                    // Add actual rooms
                    facility.rooms.forEach(room => {
                        roomSelect.innerHTML += `
                            <option value="${room.id}">
                                ${room.room_number} - ${room.description} (Capacity: ${room.capacity})
                            </option>
                        `;
                    });
                    
                    // Add label + append to container
                    const label = document.createElement('label');
                    label.className = 'form-label fw-bold';
                    label.textContent = 'Select Room:';
                    roomContainer.appendChild(label);
                    roomContainer.appendChild(roomSelect);
                    
                } else {
                    // If no rooms, simply show facility name
                    const facilityNameInput = document.createElement('input');
                    facilityNameInput.type = 'text';
                    facilityNameInput.className = 'form-control';
                    facilityNameInput.value = facility.name;
                    facilityNameInput.readOnly = true;
                    facilityNameInput.name = `facilityBookings[${bookingIdx}][facility_name]`;
                    
                    const label = document.createElement('label');
                    label.className = 'form-label fw-bold';
                    label.textContent = 'Facility:';
                    roomContainer.appendChild(label);
                    roomContainer.appendChild(facilityNameInput);
                }
            }
        }
    });
    ``
    // 5) Toggle Venue & Address Fields Based on Activity Type
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
    // Initially hide it
    venueAddressContainer.style.display = 'none';
    onCampusCheckbox.addEventListener('change', toggleVenueAddress);
    offCampusCheckbox.addEventListener('change', toggleVenueAddress);

    // 6) Only one activity type can be selected
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

   
   // 7) Function that checks booking conflicts on the fly
async function checkBookingConflicts(facilityId, roomId, date, startTime, endTime) {
    try {
        console.log("Checking conflicts with:", { facilityId, roomId, date, startTime, endTime });

        const response = await fetch('check_conflicts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                facility_id: facilityId,
                room_id: roomId,
                date: date,
                start_time: startTime,
                end_time: endTime
            })
        });

        const data = await response.json();
        console.log("Conflict Check Response:", data);
        return data;
    } catch (error) {
        console.error('Error checking conflicts:', error);
        return { error: 'Failed to check conflicts' };
    }
}


    // For quick display of 12-hour times if needed
    function formatTime12Hour(time24) {
        if (!time24) return '';
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }

    // 8) Whenever fields change, do a quick conflict check in that slot
    document.getElementById("facilityBookingsContainer").addEventListener("change", async function(e) {
        // If a date/time/facility/room changes, re-check the conflict for that slot
        if (e.target.matches('input[type="time"], input[type="date"], select.facility-select, select[name$="[room]"]')) {
            const slot = e.target.closest('.time-slot') || e.target.closest('.time-slot-card');
            const bookingBlock = e.target.closest('.facility-booking');
            const facilityId = bookingBlock.querySelector('.facility-select').value;
            const roomSelect = bookingBlock.querySelector('select[name$="[room]"]');
            const roomId = roomSelect ? roomSelect.value : null;
            
            const dateInput = slot.querySelector('input[type="date"]');
            const startInput = slot.querySelector('input[name$="[start]"]');
            const endInput = slot.querySelector('input[name$="[end]"]');

            // Clear existing warnings if anything is empty
            if (!facilityId || !dateInput.value || !startInput.value || !endInput.value) {
                slot.querySelectorAll('.conflict-warning, .suggestion-box').forEach(el => el.remove());
                return;
            }

            // Show a "checking" indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'checking-indicator';
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
            slot.appendChild(loadingIndicator);

            try {
                const response = await checkBookingConflicts(facilityId, roomId, dateInput.value, startInput.value, endInput.value);

                // Remove loading indicator & any existing warnings
                loadingIndicator.remove();
                slot.querySelectorAll('.conflict-warning, .suggestion-box').forEach(el => el.remove());

                if (response.hasConflicts) {
                    // Show conflict
                    const warningBox = document.createElement('div');
                    warningBox.className = 'conflict-warning alert alert-warning mt-2';
                    
                    let warningHTML = `
                        <strong><i class="fas fa-exclamation-triangle"></i> Booking Conflict</strong><br>
                        This time slot conflicts with existing bookings:<br>
                        ${response.existingBookings.map(booking => `
                            <div class="existing-booking">
                                <i class="fas fa-clock"></i> ${booking.formatted_start} - ${booking.formatted_end}
                                ${booking.room_number ? `<br><i class="fas fa-door-open"></i> Room ${booking.room_number}` : ''}
                                <span class="booking-status ${booking.status.toLowerCase()}">${booking.status}</span>
                            </div>
                        `).join('')}
                    `;

                    // If there are suggested slots
                    if (response.suggestedSlots && response.suggestedSlots.length > 0) {
                        warningHTML += `
                            <div class="suggestions-container mt-3">
                                <strong><i class="fas fa-lightbulb"></i> Available alternatives:</strong>
                                <div class="suggested-slots">
                                    ${response.suggestedSlots.map((slot, index) => `
                                        <button type="button" 
                                            class="btn btn-sm btn-outline-success suggested-slot"
                                            data-date="${slot.date}"
                                            data-start="${slot.start_24}"
                                            data-end="${slot.end_24}">
                                            <i class="fas fa-calendar-check"></i>
                                            ${new Date(slot.date).toLocaleDateString()} 
                                            ${slot.start} - ${slot.end}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    warningBox.innerHTML = warningHTML;
                    slot.appendChild(warningBox);

                    // Add click handlers for suggestions
                    warningBox.querySelectorAll('.suggested-slot').forEach(button => {
                        button.addEventListener('click', async function() {
                            const date = this.dataset.date;
                            const start = this.dataset.start;
                            const end = this.dataset.end;

                            if (confirm(`Would you like to book this slot instead?\n${date} ${start}-${end}`)) {
                                dateInput.value = date;
                                startInput.value = start;
                                endInput.value = end;
                                warningBox.remove();
                                
                                // Create and dispatch custom event with the new booking details
                                const checkEvent = new CustomEvent('checkConflict', {
                                    detail: {
                                        date: date,
                                        start: start,
                                        end: end,
                                        facilityId: facilityId,
                                        roomId: roomId
                                    }
                                });
                                
                                // Show immediate checking indicator
                                const loadingIndicator = document.createElement('div');
                                loadingIndicator.className = 'checking-indicator';
                                loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
                                slot.appendChild(loadingIndicator);

                                // Check conflicts for the suggested slot
                                try {
                                    const response = await checkBookingConflicts(
                                        facilityId,
                                        roomId,
                                        date,
                                        start,
                                        end
                                    );

                                    loadingIndicator.remove();

                                    if (!response.hasConflicts) {
                                        const successIndicator = document.createElement('div');
                                        successIndicator.className = 'alert alert-success mt-2';
                                        successIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Time slot available!';
                                        slot.appendChild(successIndicator);
                                        
                                        setTimeout(() => successIndicator.remove(), 2000);
                                        document.querySelector('button[type="submit"]').disabled = false;
                                    } else {
                                        // If somehow there's still a conflict, show the warning
                                        startInput.dispatchEvent(new Event('change'));
                                    }
                                } catch (error) {
                                    console.error('Error checking conflicts:', error);
                                    loadingIndicator.remove();
                                    
                                    const errorIndicator = document.createElement('div');
                                    errorIndicator.className = 'alert alert-danger mt-2';
                                    errorIndicator.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error checking availability.';
                                    slot.appendChild(errorIndicator);
                                    setTimeout(() => errorIndicator.remove(), 2000);
                                }
                            }
                        });
                    });

                    // Also disable the submit button
                    document.querySelector('button[type="submit"]').disabled = true;
                } else {
                    // Show success
                    const successIndicator = document.createElement('div');
                    successIndicator.className = 'alert alert-success mt-2';
                    successIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Time slot available!';
                    slot.appendChild(successIndicator);

                    // Hide success after short delay
                    setTimeout(() => successIndicator.remove(), 2000);

                    // Re-enable submit if everything’s okay
                    document.querySelector('button[type="submit"]').disabled = false;
                }
            } catch (error) {
                console.error('Error checking conflicts:', error);
                loadingIndicator.remove();
                
                // Show error message
                const errorIndicator = document.createElement('div');
                errorIndicator.className = 'alert alert-danger mt-2';
                errorIndicator.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error checking availability. Please try again.';
                slot.appendChild(errorIndicator);
            }
        }
    });

    // Add this after existing initialization code
    initializeDateValidation();

    function initializeDateValidation() {
        // Set date constraints for all date inputs
        const dateInputs = document.querySelectorAll('input[type="date"]');
        const today = new Date().toISOString().split('T')[0];
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 6);
        const maxDateStr = maxDate.toISOString().split('T')[0];

        dateInputs.forEach(input => {
            // Set min and max dates
            input.min = today;
            input.max = maxDateStr;

            // Add validation on change
            input.addEventListener('change', function() {
                validateDate(this);
            });
        });

        // Add validation for dynamically added date inputs
        const container = document.getElementById('facilityBookingsContainer');
        if (container) {
            container.addEventListener('change', function(e) {
                if (e.target.type === 'date') {
                    validateDate(e.target);
                }
            });
        }
    }

    function validateDate(input) {
        const selectedDate = new Date(input.value);
        selectedDate.setHours(0, 0, 0, 0); // Reset time component
        
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time component
        
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 6);
        maxDate.setHours(23, 59, 59, 999); // Set to end of day
        
        // Check if date is in the past
        if (selectedDate < today) {
            alert('Cannot select past dates. Please select a future date.');
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            input.value = tomorrow.toISOString().split('T')[0];
            return false;
        }
        
        // Check if date is too far in the future
        if (selectedDate > maxDate) {
            alert('Cannot book more than 6 months in advance.');
            input.value = maxDate.toISOString().split('T')[0];
            return false;
        }

        // Check if it's a Sunday
        if (selectedDate.getDay() === 0) {
            alert('Sunday bookings are not allowed.');
            input.value = '';
            return false;
        }

        return true;
    }

    // Update the addTimeSlot function to include date validation
    const originalAddTimeSlot = addTimeSlot;
    addTimeSlot = function(container) {
        originalAddTimeSlot(container);
        const newSlot = container.lastElementChild;
        const dateInput = newSlot.querySelector('input[type="date"]');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            const maxDate = new Date();
            maxDate.setMonth(maxDate.getMonth() + 6);
            dateInput.min = today;
            dateInput.max = maxDate.toISOString().split('T')[0];
            dateInput.addEventListener('change', function() {
                validateDate(this);
            });
        }
    };

    function addBooking() {
        bookingIndex++;
        const container = document.getElementById("facilityBookingsContainer");
        
        const newBookingHtml = `
            <div class="card mb-3 facility-booking mt-3" data-index="${bookingIndex}">
                <div class="card-body">
                    <div class="booking-header">
                        <h5 class="card-title">Booking #<span class="booking-number">${bookingIndex + 1}</span></h5>
                        <div class="booking-actions">
                            <button type="button" class="remove-booking btn btn-outline-danger btn-sm" title="Remove Booking">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="facilitySelect_${bookingIndex}" class="form-label">Select Facility</label>
                                <select class="form-select facility-select" id="facilitySelect_${bookingIndex}" 
                                    name="facilityBookings[${bookingIndex}][facility]" data-index="${bookingIndex}">
                                    <option value="">-- Select Facility --</option>
                                    ${generateFacilityOptions()}
                                </select>
                            </div>
                            <div class="room-selection mb-3">
                                <!-- Room options will be dynamically added here -->
                            </div>
                        </div>
                    </div>

                    <!-- Time Slots Container -->
                    <div class="time-slots-container">
                        <h6 class="slots-header">Time Slots</h6>
                        <div class="time-slots" data-index="${bookingIndex}">
                            <div class="time-slot-card" data-index="0">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Date</label>
                                        <input type="date"
                                            class="form-control"
                                            name="facilityBookings[${bookingIndex}][slots][0][date]"
                                            required
                                            min="${getTomorrow()}"
                                            max="${getMaxDate()}"
                                            value="${getTomorrow()}"
                                            data-date-validation="true">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Start Time:</label>
                                        <input type="time" class="form-control time-input"
                                            name="facilityBookings[${bookingIndex}][slots][0][start]"
                                            data-display-format="12"
                                            onchange="updateTimeDisplay(this)">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">End Time:</label>
                                        <input type="time" class="form-control time-input"
                                            name="facilityBookings[${bookingIndex}][slots][0][end]"
                                            data-display-format="12"
                                            onchange="updateTimeDisplay(this)">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="slot-actions">
                                            <button type="button" class="addSlot btn btn-outline-primary btn-sm" title="Add Time Slot">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" class="removeSlot btn btn-outline-danger btn-sm" title="Remove Slot">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="conflict-container mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', newBookingHtml);
        
        // Initialize the new booking's elements
        const newBooking = container.lastElementChild;
        initializeDateValidation(newBooking);
        initializeFacilitySelect(newBooking);
    }

    // Helper functions for dates
    function getTomorrow() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return tomorrow.toISOString().split('T')[0];
    }

    function getMaxDate() {
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 6);
        return maxDate.toISOString().split('T')[0];
    }
});

document.addEventListener('DOMContentLoaded', function () {
    // Attach event listeners to date input fields
    document.getElementById('start-date').addEventListener('change', checkConflict);
    document.getElementById('end-date').addEventListener('change', checkConflict);
});

function checkConflict() {
    let start_date = document.getElementById('start-date').value;
    let end_date = document.getElementById('end-date').value;

    if (!start_date || !end_date) return;

    fetch('check_date_conflicts.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ start_date, end_date })
    })
    .then(response => response.json())
    .then(data => {
        let conflictContainer = document.getElementById('date-conflicts');
        let conflictList = document.getElementById('conflicts-list');

        if (data.conflict) {
            let conflictMsg = `<h5><i class="fas fa-exclamation-triangle"></i> Schedule Conflict Detected</h5>`;
            data.conflicts.forEach(conflict => {
                let statusBadge = '';
                
                // Color-code statuses
                switch (conflict.status) {
                    case 'Approved':
                        statusBadge = `<span class="badge bg-success">Approved</span>`;
                        break;
                    case 'Pending':
                        statusBadge = `<span class="badge bg-warning">Pending</span>`;
                        break;
                    case 'Rejected':
                        statusBadge = `<span class="badge bg-danger">Rejected</span>`;
                        break;
                    default:
                        statusBadge = `<span class="badge bg-secondary">${conflict.status}</span>`;
                }

                conflictMsg += `
                    <div class="alert alert-warning">
                        <strong>Activity:</strong> ${conflict.activity_title} <br>
                        <strong>Club:</strong> ${conflict.club_name} <br>
                        <strong>Scheduled:</strong> ${conflict.start_date} - ${conflict.end_date} <br>
                        <strong>Status:</strong> ${statusBadge} <br>
                        <strong>Facility:</strong> ${conflict.facility_name}
                    </div>
                `;
            });

            conflictList.innerHTML = conflictMsg;
            conflictContainer.style.display = 'block';
        } else {
            conflictContainer.style.display = 'none';
            conflictList.innerHTML = '';
        }
    })
    .catch(error => console.error('Error:', error));
}


document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById('submitProposalForm');
    if (!form) return;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        
        if (!confirm('Are you sure you want to submit this proposal?')) return;

        try {
            const formData = new FormData(this);
            await fetch('process_proposal.php', {
                method: 'POST',
                body: formData
            });

            alert('Proposal submitted successfully!');
            window.location.href = '/main/IntelliDocM/client.php';
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Event listener for dynamic conflict checking
    document.getElementById("facilityBookingsContainer").addEventListener("change", async function (e) {
        if (e.target.matches('input[type="time"], input[type="date"], select.facility-select, select[name$="[room]"]')) {
            await checkAndHandleBookingConflicts(e.target);
        }
    });

    // Add facility selection handler
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('facility-select')) {
            handleFacilitySelection(e.target);
        }
    });

    // Initialize any existing facility selects
    document.querySelectorAll('.facility-select').forEach(select => {
        if (select.value) {
            handleFacilitySelection(select);
        }
    });

    // Function to check conflicts and suggest alternative slots
    async function checkAndHandleBookingConflicts(element) {
        const slot = element.closest('.time-slot-card');
        const bookingBlock = element.closest('.facility-booking');
    
        const facilityId = bookingBlock.querySelector('.facility-select').value;
        const dateInput = slot.querySelector('input[type="date"]');
        const startInput = slot.querySelector('input[name$="[start]"]');
        const endInput = slot.querySelector('input[name$="[end]"]');
    
        if (!facilityId || !dateInput.value || !startInput.value || !endInput.value) return;
    
        showLoadingIndicator(slot);
    
        try {
            const response = await checkBookingConflicts(facilityId, dateInput.value, startInput.value, endInput.value);
    
            clearConflictWarnings(slot);
    
            if (response.hasConflicts) {
                const validBackToBack = response.conflicts.every(conflict => 
                    conflict.start_time === endInput.value || conflict.end_time === startInput.value
                );
    
                if (validBackToBack) {
                    displaySuccessIndicator(slot);
                    document.querySelector('button[type="submit"]').disabled = false;
                } else {
                    displayConflictWarning(slot, response.existingBookings, response.suggestedSlots);
                    document.querySelector('button[type="submit"]').disabled = true;
                }
            } else {
                displaySuccessIndicator(slot);
                document.querySelector('button[type="submit"]').disabled = false;
            }
        } catch (error) {
            console.error('Error checking conflicts:', error);
            displayErrorIndicator(slot);
        }
    }
    

    // Fetch conflict data from PHP
    async function checkBookingConflicts(facilityId, date, startTime, endTime) {
        try {
            const response = await fetch('check_conflicts.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ facility_id: facilityId, date, start_time: startTime, end_time: endTime })
            });

            if (!response.ok) throw new Error("Network error: Unable to fetch conflicts");
            return await response.json();
        } catch (error) {
            console.error("Conflict check failed:", error);
            return { error: "Could not check conflicts. Try again later." };
        }
    }

    // Display conflict warning and suggested slots
    function displayConflictWarning(slot, existingBookings, suggestedSlots) {
        const warningBox = document.createElement('div');
        warningBox.className = 'conflict-warning alert alert-warning mt-2';
        warningBox.innerHTML = `<strong><i class="fas fa-exclamation-triangle"></i> Booking Conflict</strong><br>
            This time slot conflicts with existing bookings:<br>
            ${existingBookings.map(booking => `
                <div class="submitted-booking">
                    <i class="fas fa-clock"></i> ${booking.formatted_start} - ${booking.formatted_end}
                    <span class="booking-status ${booking.status.toLowerCase()}">${booking.status}</span>
                </div>
            `).join('')}`;
    
        if (suggestedSlots.length > 0) {
            console.log("Suggested slots received:", suggestedSlots); // Debugging log
            const suggestionsContainer = document.createElement('div');
            suggestionsContainer.className = 'suggestions-container mt-3';
            suggestionsContainer.innerHTML = `<strong><i class="fas fa-lightbulb"></i> Suggested Alternative Slots:</strong>
                <div class="suggested-slots">
                    ${suggestedSlots.map(slot => `
                        <button type="button" class="btn btn-sm btn-outline-success suggested-slot"
                            data-date="${slot.date}" data-start="${slot.start_24}" data-end="${slot.end_24}">
                            <i class="fas fa-calendar-check"></i>
                            ${new Date(slot.date).toLocaleDateString()} ${slot.start} - ${slot.end}
                        </button>
                    `).join('')}
                </div>`;
    
            warningBox.appendChild(suggestionsContainer);
    
            // Attach event listeners to suggested slot buttons
            suggestionsContainer.querySelectorAll('.suggested-slot').forEach(button => {
                button.addEventListener('click', function () {
                    applySuggestedSlot(slot, this.dataset.date, this.dataset.start, this.dataset.end);
                });
            });
        } else {
            console.warn("No suggested slots found!");
        }
    
        slot.appendChild(warningBox);
    }
    
    // Apply suggested slot values
    function applySuggestedSlot(slot, date, start, end) {
        const dateInput = slot.querySelector('input[type="date"]');
        const startInput = slot.querySelector('input[name$="[start]"]');
        const endInput = slot.querySelector('input[name$="[end]"]');
    
        // Update the fields with the suggested slot
        dateInput.value = date;
        startInput.value = start;
        endInput.value = end;
    
        // Trigger change events to simulate user input
        dateInput.dispatchEvent(new Event('change'));
        startInput.dispatchEvent(new Event('change'));
        endInput.dispatchEvent(new Event('change'));
    
        // Re-run the conflict check automatically
        checkAndHandleBookingConflicts(dateInput);
    }
    

    // Display success indicator
    function displaySuccessIndicator(slot) {
        const successIndicator = document.createElement('div');
        successIndicator.className = 'alert alert-success mt-2';
        successIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Time slot available!';
        slot.appendChild(successIndicator);
        setTimeout(() => successIndicator.remove(), 2000);
    }

    // Show loading indicator
    function showLoadingIndicator(slot) {
        clearConflictWarnings(slot);
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'checking-indicator';
        loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
        slot.appendChild(loadingIndicator);
    }

    // Remove conflict warnings and messages
    function clearConflictWarnings(slot) {
        slot.querySelectorAll('.conflict-warning, .checking-indicator, .alert').forEach(el => el.remove());
    }

    // Display error indicator
    function displayErrorIndicator(slot) {
        const errorIndicator = document.createElement('div');
        errorIndicator.className = 'alert alert-danger mt-2';
        errorIndicator.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error checking availability.';
        slot.appendChild(errorIndicator);
        setTimeout(() => errorIndicator.remove(), 2000);
    }
});

// Add this function to handle facility selection
function handleFacilitySelection(select) {
    const facilityId = select.value;
    const bookingCard = select.closest('.facility-booking');
    const roomSelectionDiv = bookingCard.querySelector('.room-selection');
    
    if (!facilityId) {
        roomSelectionDiv.innerHTML = '';
        return;
    }

    const facility = facilitiesData[facilityId];
    
    if (facility && facility.rooms && facility.rooms.length > 0) {
        const roomsHtml = `
            <label class="form-label">Select Room</label>
            <select class="form-select" name="facilityBookings[${bookingCard.dataset.index}][room]" required>
                <option value="">-- Select Room --</option>
                ${facility.rooms.map(room => `
                    <option value="${room.id}">
                        ${room.room_number} - Capacity: ${room.capacity}
                        ${room.description ? ` (${room.description})` : ''}
                    </option>
                `).join('')}
            </select>
        `;
        roomSelectionDiv.innerHTML = roomsHtml;
    } else {
        roomSelectionDiv.innerHTML = '<div class="text-muted">No rooms available for this facility</div>';
    }
}

// Update the addBooking function to include room handling
function addNewBooking() {
    // ...existing code...
    
    // After adding the new booking, initialize facility select
    const newSelect = container.querySelector(`.facility-booking[data-index="${bookingCount}"] .facility-select`);
    newSelect.addEventListener('change', () => handleFacilitySelection(newSelect));
}

function getTimeSlotTemplate(bookingIndex, slotIndex) {
    return `
        <div class="time-slot-card" data-index="${slotIndex}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date"
                        class="form-control timeslot-date"
                        name="facilityBookings[${bookingIndex}][slots][${slotIndex}][date]"
                        required
                        data-date-validation="true">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Time</label>
                    <div class="time-input-wrapper">
                        <input type="time"
                            class="form-control time-input"
                            name="facilityBookings[${bookingIndex}][slots][${slotIndex}][start]"
                            data-display-format="12">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Time</label>
                    <div class="time-input-wrapper">
                        <input type="time"
                            class="form-control time-input"
                            name="facilityBookings[${bookingIndex}][slots][${slotIndex}][end]"
                            data-display-format="12">
                    </div>
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
    `;
}

function getNewBookingTemplate(bookingCount) {
    return `
        <div class="card mb-3 facility-booking mt-3" data-index="${bookingCount}">
            <div class="card-body">
                <div class="booking-header">
                    <h5 class="card-title">Booking #<span class="booking-number">${bookingCount + 1}</span></h5>
                    <div class="booking-actions">
                        <button type="button" class="btn btn-danger btn-sm remove-booking">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Select Facility</label>
                            <select class="form-select facility-select" name="facilityBookings[${bookingCount}][facility]" data-index="${bookingCount}" required>
                                <option value="">-- Select Facility --</option>
                                ${Object.entries(facilitiesData).map(([id, facility]) => `
                                    <option value="${id}" data-has-rooms="${facility.rooms?.length > 0}">
                                        ${facility.name}${facility.rooms?.length > 0 ? ' (Has Rooms)' : ''}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="room-selection mb-3">
                        </div>
                    </div>
                </div>
                <div class="time-slots-container">
                    <h6 class="slots-header">Time Slots</h6>
                    <div class="time-slots" data-index="${bookingCount}">
                        ${getTimeSlotTemplate(bookingCount, 0)}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Add this function after getNewBookingTemplate
function addFirstTimeSlot(bookingIndex) {
    const timeSlots = document.querySelector(`.facility-booking[data-index="${bookingIndex}"] .time-slots`);
    if (!timeSlots) return;

    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    // Clear any existing slots
    timeSlots.innerHTML = '';
    
    // Add the first time slot
    const slotHtml = getTimeSlotTemplate(bookingIndex, 0);
    timeSlots.insertAdjacentHTML('beforeend', slotHtml);
    
    // Set date restrictions if start/end dates are set
    if (startDate && endDate) {
        const dateInput = timeSlots.querySelector('.timeslot-date');
        if (dateInput) {
            dateInput.min = startDate;
            dateInput.max = endDate;
        }
    }
}

// Update the addBooking handler
document.addEventListener('DOMContentLoaded', function() {
    // ...existing code...

    // Add Booking Button Handler
    document.getElementById('addBooking').addEventListener('click', function() {
        const container = document.getElementById('facilityBookingsContainer');
        const bookingCount = container.querySelectorAll('.facility-booking').length;
        
        container.insertAdjacentHTML('beforeend', getNewBookingTemplate(bookingCount));
        const newBooking = container.lastElementChild;
        
        // Initialize facility select for the new booking
        const facilitySelect = newBooking.querySelector('.facility-select');
        if (facilitySelect) {
            facilitySelect.addEventListener('change', () => handleFacilitySelection(facilitySelect));
        }
        
        // Add first time slot
        addFirstTimeSlot(bookingCount);
    });

    // Update time slot handler
    document.addEventListener('click', function(e) {
        if (e.target.matches('.add-time-slot, .addSlot')) {
            const bookingCard = e.target.closest('.facility-booking');
            const timeSlots = bookingCard.querySelector('.time-slots');
            const slotCount = timeSlots.querySelectorAll('.time-slot-card').length;
            addTimeSlot(timeSlots, bookingCard.dataset.index, slotCount);
        }
    });

    // Initialize handlers for existing facility selects
    document.querySelectorAll('.facility-select').forEach(select => {
        select.addEventListener('change', () => handleFacilitySelection(select));
    });
});

// Remove the duplicate addTimeSlot function and keep only this one
function addTimeSlot(timeSlotContainer, bookingIndex) {
    const currentSlots = timeSlotContainer.querySelectorAll('.time-slot-card');
    const nextSlotIndex = currentSlots.length;
    
    console.log('Adding new time slot:', { bookingIndex, nextSlotIndex }); // Debug log

    const newSlot = getTimeSlotTemplate(bookingIndex, nextSlotIndex);
    timeSlotContainer.insertAdjacentHTML('beforeend', newSlot);

    // Initialize the new slot's date input with the current date restrictions
    const newSlotDateInput = timeSlotContainer.lastElementChild.querySelector('.timeslot-date');
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    if (startDate && endDate) {
        newSlotDateInput.min = startDate;
        newSlotDateInput.max = endDate;
    }

    updateTimeSlotDates();
}

// Update the click event handlers - remove duplicate handlers and simplify
document.addEventListener('DOMContentLoaded', function() {
    // ...existing code...

    // Single unified handler for add/remove slot actions
    document.addEventListener('click', function(e) {
        // Handle add slot
        if (e.target.closest('.addSlot, .add-time-slot')) {
            e.preventDefault(); // Prevent multiple triggers
            const bookingCard = e.target.closest('.facility-booking');
            if (!bookingCard) return;

            const timeSlotContainer = bookingCard.querySelector('.time-slots');
            if (!timeSlotContainer) return;

            addTimeSlot(timeSlotContainer, bookingCard.dataset.index);
        }

        // Handle remove slot
        if (e.target.closest('.removeSlot')) {
            const slotCard = e.target.closest('.time-slot-card');
            const timeSlotContainer = slotCard.closest('.time-slots');
            
            if (timeSlotContainer.querySelectorAll('.time-slot-card').length > 1) {
                slotCard.remove();
                updateSlotIndexes(timeSlotContainer);
            } else {
                alert('You must have at least one time slot.');
            }
        }
    });

    // Remove any other duplicate event listeners for addSlot
});

// Add function to update slot indexes after removal
function updateSlotIndexes(container) {
    const slots = container.querySelectorAll('.time-slot-card');
    const bookingIndex = container.closest('.facility-booking').dataset.index;
    
    slots.forEach((slot, index) => {
        slot.dataset.index = index;
        
        // Update input names
        slot.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]\[slots\]\[\d+\]/, `[${bookingIndex}][slots][${index}]`));
            }
        });
    });
}

// Add this function at the top level of your file
function updateTimeSlotDates() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;

    if (!startDate || !endDate) return;

    document.querySelectorAll('.timeslot-date').forEach(input => {
        // Set min and max dates
        input.min = startDate;
        input.max = endDate;

        // Clear date if outside new range
        if (input.value) {
            if (input.value < startDate || input.value > endDate) {
                input.value = '';
            }
        }
    });

    checkDateConflicts();
}

// Add this after the addBooking handler in the DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    // ...existing code...

    // Add Remove Booking Handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-booking')) {
            const bookingCard = e.target.closest('.facility-booking');
            const container = document.getElementById('facilityBookingsContainer');
            
            if (container.querySelectorAll('.facility-booking').length > 1) {
                bookingCard.remove();
                updateBookingIndexes(container);
            } else {
                alert('You must have at least one booking.');
            }
        }
    });

    // ...existing code...
});

// Add this new function to update booking indexes after removal
function updateBookingIndexes(container) {
    container.querySelectorAll('.facility-booking').forEach((booking, index) => {
        // Update data-index
        booking.dataset.index = index;
        
        // Update booking number display
        booking.querySelector('.booking-number').textContent = index + 1;
        
        // Update facility select name and data-index
        const facilitySelect = booking.querySelector('.facility-select');
        if (facilitySelect) {
            facilitySelect.name = `facilityBookings[${index}][facility]`;
            facilitySelect.dataset.index = index;
        }

        // Update room select name if it exists
        const roomSelect = booking.querySelector('.room-selection select');
        if (roomSelect) {
            roomSelect.name = `facilityBookings[${index}][room]`;
        }

        // Update time slots container data-index and input names
        const timeSlotsContainer = booking.querySelector('.time-slots');
        if (timeSlotsContainer) {
            timeSlotsContainer.dataset.index = index;
            
            // Update all time slot input names
            timeSlotsContainer.querySelectorAll('.time-slot-card').forEach((slot, slotIndex) => {
                slot.querySelectorAll('input').forEach(input => {
                    const name = input.getAttribute('name');
                    if (name) {
                        input.setAttribute('name', name.replace(/facilityBookings\[\d+\]/, `facilityBookings[${index}]`));
                    }
                });
            });
        }
    });
}

// ...rest of existing code...

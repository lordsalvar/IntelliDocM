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

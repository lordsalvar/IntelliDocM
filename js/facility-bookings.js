const FacilityBookings = {
    showHistory() {
        $('#bookingHistoryModal').modal('show');
        this.loadBookingHistory();
    },

    async loadBookingHistory(filters = {}) {
        const tbody = $('#bookingHistoryBody');
        tbody.html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');

        try {
            const response = await $.get('ajax/get_booking_history.php', filters);
            if (response.success) {
                this.displayBookings(response.data);
            } else {
                tbody.html('<tr><td colspan="4" class="text-center text-danger">Failed to load history</td></tr>');
            }
        } catch (error) {
            console.error('Error loading booking history:', error);
            tbody.html('<tr><td colspan="4" class="text-center text-danger">Error loading history</td></tr>');
        }
    },

    displayBookings(bookings) {
        const tbody = $('#bookingHistoryBody');
        tbody.empty();

        bookings.forEach(booking => {
            tbody.append(`
                <tr>
                    <td>
                        <div class="booking-facility">
                            <strong>${this.escapeHtml(booking.facility_name)}</strong>
                            ${booking.room_numbers ? `
                                <span class="rooms-badge">
                                    <i class="fas fa-door-open"></i> 
                                    ${this.escapeHtml(booking.room_numbers)}
                                </span>
                            ` : ''}
                        </div>
                    </td>
                    <td>${this.escapeHtml(booking.user_name)}</td>
                    <td>
                        <div class="booking-schedule">
                            <div>${booking.formatted_date}</div>
                            <small>${booking.formatted_time}</small>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-${booking.status.toLowerCase()}">
                            ${booking.status}
                        </span>
                    </td>
                </tr>
            `);
        });
    },

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
};

// Initialize
$(document).ready(() => {
    $('#historyStatusFilter, #historyDateFilter').on('change', () => {
        const filters = {
            status: $('#historyStatusFilter').val(),
            date: $('#historyDateFilter').val()
        };
        FacilityBookings.loadBookingHistory(filters);
    });
});

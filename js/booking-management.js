function updateBookingTable(bookings) {
    const tbody = document.getElementById('bookingTableBody');
    tbody.innerHTML = '';

    bookings.forEach(booking => {
        const statusClass = getStatusClass(booking.status);
        const row = `
            <tr>
                <td>
                    <div class="booking-facility">
                        <strong>${escapeHtml(booking.facility_name)}</strong>
                        ${booking.room_numbers ? `
                            <span class="rooms-badge">
                                <i class="fas fa-door-open"></i> 
                                ${escapeHtml(booking.room_numbers)}
                            </span>
                        ` : ''}
                    </div>
                </td>
                <td>
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        ${escapeHtml(booking.user_name)}
                    </div>
                </td>
                <td>
                    <div class="schedule-info">
                        <div class="date">
                            <i class="fas fa-calendar"></i>
                            ${booking.formatted_date}
                        </div>
                        <div class="time">
                            <i class="fas fa-clock"></i>
                            ${booking.formatted_start} - ${booking.formatted_end}
                        </div>
                    </div>
                </td>
                <td>
                    <span class="status-badge status-${statusClass}">
                        ${escapeHtml(booking.status)}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        ${booking.status === 'Pending' ? `
                            <button class="btn-icon success" onclick="updateBookingStatus(${booking.id}, 'Confirmed')" 
                                    data-tooltip="Confirm">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn-icon danger" onclick="updateBookingStatus(${booking.id}, 'Cancelled')"
                                    data-tooltip="Cancel">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                        <button class="btn-icon info" onclick="viewBookingDetails(${booking.id})"
                                data-tooltip="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

function getStatusClass(status) {
    return {
        'Confirmed': 'success',
        'Cancelled': 'danger',
        'Pending': 'warning'
    }[status] || 'warning';
}

function exportBookingHistory() {
    const status = document.getElementById('statusFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    window.location.href = `export_bookings.php?status=${status}&date=${date}`;
}

// Initialize tooltips for dynamically added elements
document.addEventListener('DOMContentLoaded', () => {
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length) {
                const tooltips = document.querySelectorAll('[data-tooltip]');
                tooltips.forEach(element => {
                    new bootstrap.Tooltip(element, {
                        placement: 'top',
                        trigger: 'hover'
                    });
                });
            }
        });
    });

    observer.observe(document.getElementById('bookingTableBody'), {
        childList: true,
        subtree: true
    });
});

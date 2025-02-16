const FacilityManager = {
    init() {
        this.bindEvents();
        this.bindHistoryFilters();
    },

    bindEvents() {
        // Add Facility Form Submit
        document.getElementById('addFacilityForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addFacility();
        });

        // Search and filter bindings
        document.getElementById('facilitySearch')?.addEventListener('input', 
            this.debounce(e => this.handleSearch(e.target.value), 300)
        );

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => this.handleFilter(btn.dataset.filter));
        });
    },

    async addFacility() {
        const form = document.getElementById('addFacilityForm');
        const formData = new FormData(form);
        formData.append('action', 'add');

        try {
            const response = await fetch('ajax/facility_handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.text();
            
            if (result === 'success') {
                alert('Facility added successfully!');
                // Refresh page to show new facility
                window.location.reload();
            } else {
                alert(result || 'Failed to add facility');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error adding facility. Please try again.');
        }
    },

    updateStats(stats) {
        if (stats.total_facilities) {
            document.querySelector('.stat-card:nth-child(1) .stat-number').textContent = stats.total_facilities;
        }
        if (stats.total_rooms) {
            document.querySelector('.stat-card:nth-child(2) .stat-number').textContent = stats.total_rooms;
        }
    },

    addFacilityToUI(facility) {
        const facilitiesGrid = document.querySelector('.facilities-grid');
        const facilityHtml = `
            <div class="facility-card">
                <div class="facility-header">
                    <h3>${this.escapeHtml(facility.name)}</h3>
                    <span class="facility-code">${this.escapeHtml(facility.code)}</span>
                </div>
                <p class="facility-description">${this.escapeHtml(facility.description)}</p>
                <div class="facility-stats">
                    <div class="stat">
                        <i class="fas fa-door-open"></i>
                        <span>0 Rooms</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-calendar-check"></i>
                        <span>0 Bookings</span>
                    </div>
                </div>
                <div class="facility-actions">
                    <button class="btn-icon" onclick="FacilityManager.manageFacility(${facility.id})" title="Manage Facility">
                        <i class="fas fa-cog"></i>
                    </button>
                    <button class="btn-icon" onclick="FacilityManager.viewRooms(${facility.id})" title="View Rooms">
                        <i class="fas fa-door-open"></i>
                    </button>
                    <button class="btn-icon" onclick="FacilityManager.viewBookings(${facility.id})" title="View Bookings">
                        <i class="fas fa-calendar"></i>
                    </button>
                </div>
            </div>
        `;
        facilitiesGrid.insertAdjacentHTML('afterbegin', facilityHtml);
    },

    handleSearch(value) {
        const cards = document.querySelectorAll('.facility-card');
        const searchTerm = value.toLowerCase();

        cards.forEach(card => {
            const name = card.querySelector('h3').textContent.toLowerCase();
            const code = card.querySelector('.facility-code').textContent.toLowerCase();
            const description = card.querySelector('.facility-description').textContent.toLowerCase();

            const matches = name.includes(searchTerm) || 
                           code.includes(searchTerm) || 
                           description.includes(searchTerm);

            card.style.display = matches ? 'block' : 'none';
        });
    },

    handleFilter(type) {
        // Remove active class from all buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Add active class to clicked button
        document.querySelector(`[data-filter="${type}"]`).classList.add('active');

        // Apply filter
        const cards = document.querySelectorAll('.facility-card');
        if (type === 'all') {
            cards.forEach(card => card.style.display = 'block');
            return;
        }

        cards.forEach(card => {
            const bookings = parseInt(card.querySelector('.stat:nth-child(2) span').textContent);
            const isBooked = bookings > 0;
            const shouldShow = (type === 'booked' && isBooked) || (type === 'available' && !isBooked);
            card.style.display = shouldShow ? 'block' : 'none';
        });
    },

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    },

    showBookingHistory() {
        const modal = new bootstrap.Modal(document.getElementById('bookingHistoryModal'));
        modal.show();
        this.loadBookingHistory();
    },

    async loadBookingHistory(status = 'all', date = '') {
        try {
            const params = new URLSearchParams({ status, date });
            const response = await fetch(`ajax/get_booking_history.php?${params}`);
            const result = await response.json();
            
            if (result.success) {
                this.renderBookingHistory(result.data);
            } else {
                console.error('Server error:', result.message);
                alert(result.message || 'Failed to load booking history');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error connecting to server. Please check console for details.');
        }
    },

    renderBookingHistory(bookings) {
        const tbody = document.getElementById('bookingHistoryBody');
        tbody.innerHTML = bookings.map(booking => `
            <tr>
                <td>${this.escapeHtml(booking.facility_name)}</td>
                <td>${this.escapeHtml(booking.room_numbers || 'N/A')}</td>
                <td>${booking.booking_date}</td>
                <td>${booking.start_time} - ${booking.end_time}</td>
                <td>${this.escapeHtml(booking.club_name)}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(booking.status)}">
                        ${this.escapeHtml(booking.status)}
                    </span>
                </td>
            </tr>
        `).join('');
    },

    getStatusColor(status) {
        return {
            'Pending': 'warning',
            'Confirmed': 'success',
            'Cancelled': 'danger'
        }[status] || 'secondary';
    },

    bindHistoryFilters() {
        document.getElementById('statusFilter').addEventListener('change', e => 
            this.loadBookingHistory(e.target.value, document.getElementById('dateFilter').value)
        );
        
        document.getElementById('dateFilter').addEventListener('change', e => 
            this.loadBookingHistory(document.getElementById('statusFilter').value, e.target.value)
        );
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => FacilityManager.init());

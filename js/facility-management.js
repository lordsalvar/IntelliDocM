const FacilityManager = {
    init() {
        this.bindEvents();
        this.bindHistoryFilters();
        this.bindManageFacility();
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
                    <button class="btn-icon manage-facility" data-facility-id="${facility.id}" title="Manage Facility">
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
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');

        // Only bind events if elements exist
        if (statusFilter && dateFilter) {
            statusFilter.addEventListener('change', e => 
                this.loadBookingHistory(e.target.value, dateFilter.value)
            );
            
            dateFilter.addEventListener('change', e => 
                this.loadBookingHistory(statusFilter.value, e.target.value)
            );
        }
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    bindManageFacility() {
        document.addEventListener('click', (e) => {
            const manageBtn = e.target.closest('.manage-facility');
            if (manageBtn) {
                const facilityId = manageBtn.dataset.facilityId;
                this.manageFacility(facilityId);
            }
        });
    },

    manageFacility(facilityId) {
        const manageFacilityModal = new bootstrap.Modal(document.getElementById('manageFacilityModal'));
        const manageFacilityEl = document.getElementById('manageFacilityModal');
        
        fetch('ajax/manage_facility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get&facilityId=${facilityId}`
        })
        .then(response => response.json())
        .then(facility => {
            if (facility) {
                // Populate facility details
                document.getElementById('manageFacilityId').value = facility.id;
                document.getElementById('manageFacilityName').value = facility.name;
                document.getElementById('manageFacilityCode').value = facility.code;
                document.getElementById('manageFacilityDescription').value = facility.description || '';
                
                this.loadFacilityRooms(facilityId);
                manageFacilityModal.show();

                // Simple add room button handler
                document.getElementById('addRoomBtn').onclick = () => {
                    // Dim the manage facility modal
                    manageFacilityEl.classList.add('manage-facility-dimmed');
                    
                    const addRoomModal = new bootstrap.Modal(document.getElementById('addRoomModal'), {
                        backdrop: 'static'
                    });
                    
                    // Set up facility ID for the form
                    document.getElementById('addRoomForm').dataset.facilityId = facilityId;
                    
                    // Handle closing of add room modal
                    document.getElementById('addRoomModal').addEventListener('hidden.bs.modal', () => {
                        manageFacilityEl.classList.remove('manage-facility-dimmed');
                    }, { once: true });
                    
                    addRoomModal.show();
                };

                // Add delete button handler
                document.getElementById('deleteFacilityBtn').onclick = () => {
                    if (confirm('Are you sure you want to delete this facility? This action cannot be undone.')) {
                        fetch('ajax/manage_facility.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=delete&facilityId=${facilityId}`
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert('Facility deleted successfully!');
                                window.location.reload();
                            } else {
                                throw new Error(result.message || 'Failed to delete facility');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert(error.message);
                        });
                    }
                };

                // Add room form submit handler
                document.getElementById('addRoomForm').onsubmit = (e) => {
                    e.preventDefault();
                    const form = e.target;
                    const facilityId = form.dataset.facilityId;
                    
                    const formData = new FormData();
                    formData.append('action', 'add_room');
                    formData.append('facilityId', facilityId);
                    formData.append('roomNumber', document.getElementById('roomNumber').value);
                    formData.append('capacity', document.getElementById('roomCapacity').value);
                    formData.append('description', document.getElementById('roomDescription').value);

                    fetch('ajax/room_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            // Close the add room modal
                            bootstrap.Modal.getInstance(document.getElementById('addRoomModal')).hide();
                            
                            // Reset form
                            form.reset();
                            
                            // Show success message
                            alert('Room added successfully!');
                            
                            // Reload rooms list
                            this.loadFacilityRooms(facilityId);
                        } else {
                            throw new Error(result.message || 'Failed to add room');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert(error.message || 'Failed to add room');
                    });
                };
            } else {
                throw new Error('Facility not found');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load facility details');
        });
    },

    loadFacilityRooms(facilityId) {
        fetch('ajax/room_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_rooms&facilityId=${facilityId}`
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tbody = document.getElementById('roomsTableBody');
                if (result.data.length > 0) {
                    tbody.innerHTML = result.data.map(room => `
                        <tr>
                            <td>${this.escapeHtml(room.room_number)}</td>
                            <td>${room.capacity} persons</td>
                            <td>${this.escapeHtml(room.description || '-')}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="FacilityManager.deleteRoom(${room.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">No rooms available</td></tr>';
                }
            }
        })
        .catch(error => console.error('Error loading rooms:', error));
    }
};

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => FacilityManager.init());

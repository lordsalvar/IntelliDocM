<!-- Add Facility Modal -->
<div class="modal fade" id="addFacilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addFacilityForm" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="facilityName" class="form-label">Facility Name</label>
                        <input type="text" class="form-control" id="facilityName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="facilityCode" class="form-label">Facility Code</label>
                        <input type="text" class="form-control" id="facilityCode" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="facilityDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="facilityDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Facility</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Facility Modal -->
<div class="modal fade" id="editFacilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editFacilityForm">
                <input type="hidden" id="editFacilityId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editFacilityName" class="form-label">Facility Name</label>
                        <input type="text" class="form-control" id="editFacilityName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFacilityCode" class="form-label">Facility Code</label>
                        <input type="text" class="form-control" id="editFacilityCode" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFacilityDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editFacilityDescription" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Rooms Modal -->
<div class="modal fade" id="viewRoomsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-door-open"></i> Facility Rooms</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <h6 class="facility-name"></h6>
                    <button class="btn btn-primary btn-sm" onclick="showAddRoomForm()">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>
                <div class="rooms-container">
                    <!-- Rooms will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-door-open"></i> Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRoomForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="roomNumber" class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="roomNumber" required>
                    </div>
                    <div class="mb-3">
                        <label for="roomCapacity" class="form-label">Max. Capacity</label>
                        <input type="number" class="form-control" id="roomCapacity" value="30" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="roomDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="roomDescription" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-check"></i> Facility Bookings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="booking-filters mb-3">
                    <select class="form-select" id="bookingStatusFilter">
                        <option value="all">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <input type="date" class="form-control" id="bookingDateFilter">
                </div>
                <div class="bookings-container">
                    <!-- Bookings will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Facility Bookings Modal -->
<div class="modal fade" id="facilityBookingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Bookings will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Facility Bookings Modal -->
<div class="modal fade" id="facilityBookingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Bookings will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmationMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Booking History Modal -->
<div class="modal fade" id="bookingHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history"></i> Booking History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Facility</th>
                                <th class="text-center">Room(s)</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Time</th>
                                <th class="text-center">Organization User</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="bookingHistoryBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manage Facility Modal -->
<div class="modal fade" id="manageFacilityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog"></i> Manage Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Facility Details Form -->
                <form id="manageFacilityForm" class="mb-4">
                    <input type="hidden" id="manageFacilityId">
                    <div class="mb-3">
                        <label for="manageFacilityName" class="form-label">Facility Name</label>
                        <input type="text" class="form-control" id="manageFacilityName" required>
                    </div>
                    <div class="mb-3">
                        <label for="manageFacilityCode" class="form-label">Facility Code</label>
                        <input type="text" class="form-control" id="manageFacilityCode" required>
                    </div>
                    <div class="mb-3">
                        <label for="manageFacilityDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="manageFacilityDescription" rows="3"></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" id="deleteFacilityBtn">
                            <i class="fas fa-trash"></i> Delete Facility
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>

                <!-- Rooms Section -->
                <hr class="my-4">
                <div class="rooms-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-door-open"></i> Facility Rooms</h5>
                        <button class="btn btn-primary btn-sm" id="addRoomBtn">
                            <i class="fas fa-plus"></i> Add Room
                        </button>
                    </div>
                    <div id="roomsList" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Max. Capacity</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="roomsTableBody">
                                <!-- Rooms will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
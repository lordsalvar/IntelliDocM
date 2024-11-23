    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Notifications</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="list-group">
                        <!-- Activity Proposal Notification -->
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <strong>Activity Proposal Form</strong><br>
                                    <small class="text-muted">Submitted on: 2024-11-15</small><br>
                                    Status: <span class="badge badge-success">Approved</span>
                                </div>
                                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                                    <button class="btn btn-primary btn-sm" onclick="viewDocument('proposal')">
                                        View Document
                                    </button>
                                </div>
                            </div>
                        </li>
                        <!-- Booking Form Notification -->
                        <li class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <strong>Booking Form</strong><br>
                                    <small class="text-muted">Submitted on: 2024-11-11</small><br>
                                    Status: <span class="badge badge-danger">Disapproved</span>
                                </div>
                                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                                    <button class="btn btn-primary btn-sm" onclick="viewDocument('booking')">
                                        View Document
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
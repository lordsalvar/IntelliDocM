<!-- Add Club Modal -->
<div class="modal fade" id="addClubModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Organization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addClubForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Organization Name:</label>
                        <input type="text" name="clubName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Acronym:</label>
                        <input type="text" name="acronym" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Type:</label>
                        <select name="type" class="form-control" required>
                            <option value="Academic">Academic</option>
                            <option value="Non-Academic">Non-Academic</option>
                            <option value="ACCO">ACCO</option>
                            <option value="CSG">CSG</option>
                            <option value="College-LGU">College-LGU</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Moderator:</label>
                        <input type="text" name="moderator" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Edit Club Members Modal -->
<div class="modal fade" id="clubMembersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Club Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary" id="addMemberBtn">
                        <i class="fas fa-plus"></i> Add Member
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table" id="membersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addMemberForm">
                <div class="modal-body">
                    <input type="hidden" name="clubId" id="memberClubId">
                    <div class="mb-3">
                        <label>Full Name:</label>
                        <input type="text" name="fullName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Contact:</label>
                        <input type="text" name="contact" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Role:</label>
                        <select name="role" class="form-control" required>
                            <option value="member">Member</option>
                            <option value="officer">Officer</option>
                            <option value="moderator">Moderator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
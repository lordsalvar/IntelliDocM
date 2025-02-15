const ClubManager = {
    currentClubId: null,

    init() {
        this.loadClubs();
        this.bindEvents();
    },

    bindEvents() {
        $('#addClubForm').on('submit', (e) => this.handleAddClub(e));
        $('#addMemberForm').on('submit', (e) => this.handleAddMember(e));
        $('#clubSearch').on('input', this.debounce((e) => this.searchClubs(e.target.value), 300));
        
        // Filter buttons
        $('.filter-btn').click(function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            ClubManager.filterClubs($(this).data('filter'));
        });
    },

    async loadClubs() {
        try {
            const response = await $.get('ajax/club_handler.php', { action: 'fetchClubs' });
            this.displayClubs(response.data);
        } catch (error) {
            this.showAlert('Error loading clubs', 'error');
        }
    },

    async searchClubs(searchTerm) {
        try {
            const response = await $.post('ajax/club_handler.php', {
                action: 'search',
                term: searchTerm
            });
            if (response.success) {
                this.displayClubs(response.data);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    },

    displayClubs(clubs) {
        const tbody = $('#clubTableBody');
        tbody.empty();

        clubs.forEach(club => {
            const row = `
                <tr data-type="${club.club_type.toLowerCase()}">
                    <td>${this.escapeHtml(club.club_name)}</td>
                    <td>${this.escapeHtml(club.club_type)}</td>
                    <td>
                        <span class="member-badge" role="button" onclick="ClubManager.viewMembers(${club.club_id})">
                            <i class="fas fa-users"></i> ${club.member_count}
                        </span>
                    </td>
                    <td>${this.escapeHtml(club.moderator)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-primary" onclick="ClubManager.viewMembers(${club.club_id})">
                                <i class="fas fa-users"></i>
                            </button>
                            <button class="btn-action btn-warning" onclick="ClubManager.editClub(${club.club_id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-danger" onclick="ClubManager.deleteClub(${club.club_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    },

    async viewMembers(clubId) {
        this.currentClubId = clubId;
        try {
            const response = await $.get('ajax/club_handler.php', { 
                action: 'getMembers',
                clubId: clubId
            });
            this.displayMembers(response.data);
            $('#clubMembersModal').modal('show');
        } catch (error) {
            this.showAlert('Error loading members', 'error');
        }
    },

    displayMembers(members) {
        const tbody = $('#membersTable tbody');
        tbody.empty();

        members.forEach(member => {
            const row = `
                <tr>
                    <td>${this.escapeHtml(member.full_name)}</td>
                    <td>${this.escapeHtml(member.role)}</td>
                    <td>${this.escapeHtml(member.contact)}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="ClubManager.editMember(${member.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="ClubManager.removeMember(${member.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    },

    async handleAddClub(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const response = await $.post('ajax/club_handler.php', {
                action: 'add_club',
                ...Object.fromEntries(formData)
            });
            
            if (response.success) {
                this.showAlert('Club added successfully', 'success');
                this.loadClubs();
                $('#addClubModal').modal('hide');
                e.target.reset();
            } else {
                this.showAlert(response.message, 'error');
            }
        } catch (error) {
            this.showAlert('Failed to add club', 'error');
        }
    },

    showAlert(message, type = 'success') {
        const alertBox = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.alert-container').html(alertBox);
        setTimeout(() => $('.alert').alert('close'), 3000);
    },

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/<//g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    },

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

$(document).ready(() => ClubManager.init());

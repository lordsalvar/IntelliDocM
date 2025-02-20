<?php
// activity_proposal_form.php

include_once 'config.php';
include_once 'functions.php';
include_once 'system_log/activity_log.php';
include_once 'functions.php';  // Ensure this file is included

// Ensure the user is a client; otherwise, redirect
if ($_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Log the activity of visiting the proposal form
$username = $_SESSION['username'];
// $userActivity = 'User visited Activity Proposal Form';
// logActivity($username, $userActivity);

// Fetch facilities and their rooms from the database


$facilities = getFacilitiesWithRooms($conn);

// Set a default facility as selected (for example, the first facility in the list)
$selectedFacility = isset($_GET['facility']) ? (int)$_GET['facility'] : key($facilities);


// Fetch club and applicant details
$user_id = $_SESSION['user_id'];
$club_data = getClubData($conn, $user_id);
$applicant_details = getApplicantDetails($conn, $user_id);
$applicant_name = $applicant_details['applicant_name'];
$applicant_contact = $applicant_details['applicant_contact'];
$moderator_data = isset($club_data['club_id']) ? getModeratorData($conn, $club_data['club_id']) : ['moderator_name' => '', 'designation' => ''];
$moderator_name = $moderator_data['moderator_name'];
$dean_data = isset($club_data['club_id']) ? getDeanData($conn, $club_data['club_id']) : ['dean_name' => ''];
$dean_name = $dean_data['dean_name'];

// Add this PHP function after your existing includes
function getExistingActivities($conn, $startDate, $endDate)
{
    $sql = "SELECT 
        ap.activity_title,
        ap.activity_date,
        ap.end_activity_date,
        ap.club_name,
        GROUP_CONCAT(DISTINCT CONCAT(f.name, ' - ', GROUP_CONCAT(r.room_number)) SEPARATOR '; ') as booked_facilities
    FROM activity_proposals ap
    LEFT JOIN bookings b ON DATE(b.booking_date) BETWEEN ap.activity_date AND ap.end_activity_date
    LEFT JOIN facilities f ON b.facility_id = f.id
    LEFT JOIN booking_rooms br ON b.id = br.booking_id
    LEFT JOIN rooms r ON br.room_id = r.id
    WHERE (
        (ap.activity_date BETWEEN ? AND ?) OR
        (ap.end_activity_date BETWEEN ? AND ?) OR
        (? BETWEEN ap.activity_date AND ap.end_activity_date)
    )
    AND ap.status = 'confirmed'
    GROUP BY ap.proposal_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $startDate, $endDate, $startDate, $endDate, $startDate);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Your code that gets elements (including roomOrBuildingInput) goes here.
            // For example:
            const roomOrBuildingInput = document.getElementById("yourRoomInputId");
            // .... rest of your code
        });
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Activity Proposal Form</title>
    <link href="css/act_Pro.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>
        .back-button {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .back-button.fixed {
            position: fixed;
            background: rgba(33, 37, 41, 0.9);
            color: white !important;
            backdrop-filter: blur(5px);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transform: translateY(-50%);
            top: 2rem;
        }

        .back-button.fixed:hover {
            background: rgba(33, 37, 41, 1);
            transform: translateY(-50%) translateX(5px);
        }
    </style>
</head>

<body>


    <div class="container my-5">
        <a class="btn btn-secondary mb-3 back-button" href="/main/intellidocm/client_dashboard.php">← Back</a>
        <!-- Overlay Box -->
        <div class="overlay-box">
            <p><strong>Index No.:</strong> <u>7.3</u></p>
            <p><strong>Revision No.:</strong> <u>00</u></p>
            <p><strong>Effective Date:</strong> <u>05/16/24</u></p>
            <p><strong>Control No.:</strong> ___________</p>
        </div>

        <div class="header-content">
            <img src="images/cjc logo.jpg" alt="Logo" class="header-logo">
            <div class="header-text">
                <h2 class="text-center text-uppercase">Cor Jesu College, Inc.</h2>
                <div class="line yellow-line"></div>
                <div class="line blue-line"></div>
                <p class="text-center">Sacred Heart Avenue, Digos City, Province of Davao del Sur, Philippines</p>
                <p class="text-center">Tel. No.: (082) 553-2433 local 101 • Fax No.: (082) 553-2333 • www.cjc.edu.ph</p>
            </div>
        </div>

        <h3 class="text-center">ACTIVITY PROPOSAL FORM</h3>

        <form method="POST" action="process_proposal.php" id="submitProposalForm" enctype="multipart/form-data">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <!-- Add submission identifier -->
            <input type="hidden" name="submission_id" value="<?php echo uniqid('form_', true); ?>">

            <!-- Organization Details -->
            <div class="mb-4">
                <label for="organizationName" class="form-label">Name of the Organization/ Class/ College:</label>
                <input type="text" class="form-control" id="organizationName" name="organizationName"
                    value="<?php echo setValue($club_data['club_name']); ?>" <?php echo setReadonly($club_data['club_name']); ?> />
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="acronym" class="form-label">Acronym:</label>
                    <input type="text" class="form-control" id="acronym" name="acronym"
                        value="<?php echo setValue($club_data['acronym']); ?>" <?php echo setReadonly($club_data['acronym']); ?> />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Organization Category:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="academic" name="clubType" value="Academic"
                            <?php echo ($club_data['club_type'] === 'Academic') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="academic">Academic</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="nonAcademic" name="clubType" value="Non-Academic"
                            <?php echo ($club_data['club_type'] === 'Non-Academic') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="nonAcademic">Non-Academic</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="acco" name="clubType" value="ACCO"
                            <?php echo ($club_data['club_type'] === 'ACCO') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="acco">ACCO</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="csg" name="clubType" value="CSG"
                            <?php echo ($club_data['club_type'] === 'CSG') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="csg">CSG</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="collegeLGU" name="clubType" value="College-LGU"
                            <?php echo ($club_data['club_type'] === 'College-LGU') ? 'checked disabled' : 'disabled'; ?>>
                        <label class="form-check-label" for="collegeLGU">College-LGU</label>
                    </div>
                </div>
            </div>

            <!-- Activity Title and Type -->
            <div class="row mb-4">
                <div class="col mb-6">
                    <label for="activityTitle" class="form-label">Title of the Activity:</label>
                    <input type="text" class="form-control" id="activityTitle" name="activityTitle" placeholder="Enter activity title" />
                </div>
                <div class="col mb-6">
                    <label class="form-label">Type of Activity:</label>
                    <div class="form-check">
                        <input class="form-check-input activity-type" type="checkbox" id="on-campus" name="activityType[]" value="On-Campus Activity">
                        <label class="form-check-label" for="on-campus">On-Campus Activity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input activity-type" type="checkbox" id="off-campus" name="activityType[]" value="Off-Campus Activity">
                        <label class="form-check-label" for="off-campus">Off-Campus Activity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input activity-type" type="checkbox" id="online" name="activityType[]" value="Online Activity">
                        <label class="form-check-label" for="online">Online Activity</label>
                    </div>
                </div>
            </div>

            <!-- Objectives -->
            <div class="mb-4">
                <label class="form-label">Objectives:</label>
                <textarea class="form-control" rows="3" id="objectives" name="objectives" placeholder="List objectives here"></textarea>
            </div>

            <!-- Program Category -->
            <div class="mb-4">
                <label class="form-label">Student Development Program Category:</label>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="omp" name="omp" value="OMP">
                            <label class="form-check-label" for="omp">Organizational Management Development (OMP)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ksd" name="ksd" value="KSD">
                            <label class="form-check-label" for="ksd">Knowledge & Skills Development (KSD)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ct" name="ct" value="CT">
                            <label class="form-check-label" for="ct">Capacity and Teambuilding (CT)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="srf" name="srf" value="SRF">
                            <label class="form-check-label" for="srf">Spiritual & Religious Formation (SRF)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rpInitiative" name="rpInitiative" value="RPI">
                            <label class="form-check-label" for="rpInitiative">Research & Project Initiative (RPI)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cesa" name="cesa" value="CESA">
                            <label class="form-check-label" for="cesa">Community Engagement & Social Advocacy (CESA)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <label for="start-date" class="form-label">Start Date of the Activity:</label>
                        <input type="date" class="form-control" id="start-date" name="start_date" onchange="checkDateConflicts()" />
                    </div>
                    <div class="col-md-6">
                        <label for="end-date" class="form-label">End Date of the Activity:</label>
                        <input type="date" class="form-control" id="end-date" name="end_date" onchange="checkDateConflicts()" />
                    </div>
                </div>
                <!-- Add this div for conflicts -->
                <div id="date-conflicts" class="conflicts-container mb-4" style="display: none;">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Notice: Overlapping Activities</h5>
                        <div id="conflicts-list"></div>
                    </div>
                </div>

                <!-- Facility Bookings -->
                <div id="facilityBookingsContainer" class="booking-section">
                    <h4 class="section-title">Facility Bookings</h4>
                    <!-- Default facility booking block -->
                    <div class="card mb-3 facility-booking mt-3" data-index="0">
                        <div class="card-body">
                            <div class="booking-header">
                                <h5 class="card-title">Booking #<span class="booking-number">1</span></h5>
                                <div class="booking-actions">
                                    <button type="button" id="addBooking" class="btn btn-primary btn-sm" title="Add Another Booking">
                                        <i class="fas fa-plus-circle"></i> Add Booking
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="facilitySelect_0" class="form-label">Select Facility</label>
                                        <select class="form-select facility-select" id="facilitySelect_0" name="facilityBookings[0][facility]" data-index="0">
                                            <option value="">-- Select Facility --</option>
                                            <?php foreach ($facilities as $facilityId => $facility): ?>
                                                <option value="<?php echo $facilityId; ?>">
                                                    <?php echo htmlspecialchars($facility['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="room-selection mb-3">
                                        <!-- Room options will be dynamically added here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Time Slots Container -->
                            <div class="time-slots-container">
                                <h6 class="slots-header">Time Slots</h6>
                                <div class="time-slots" data-index="0">
                                    <div class="time-slot-card" data-index="0">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Date</label>
                                                <input type="date"
                                                    class="form-control timeslot-date"
                                                    name="facilityBookings[0][slots][0][date]"
                                                    required
                                                    data-date-validation="true">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Start Time:</label>
                                                <input type="time" class="form-control time-input"
                                                    name="facilityBookings[0][slots][0][start]"
                                                    data-display-format="12"
                                                    onchange="updateTimeDisplay(this)">

                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">End Time:</label>
                                                <input type="time" class="form-control time-input"
                                                    name="facilityBookings[0][slots][0][end]"
                                                    data-display-format="12"
                                                    onchange="updateTimeDisplay(this)">

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
                                        <!-- Conflict warnings will appear here -->
                                        <div class="conflict-container mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Participants -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="targetParticipants" class="form-label">Target Participants:</label>
                        <input type="text" class="form-control" id="targetParticipants" name="targetParticipants" placeholder="Enter target participants" />
                    </div>
                    <div class="col-md-6">
                        <label for="expectedParticipants" class="form-label">Expected Number of Participants:</label>
                        <input type="number" class="form-control" id="expectedParticipants" name="expectedParticipants" placeholder="Enter expected number" />
                    </div>
                </div>

                <!-- Signatures -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Applicant</label>
                        <input type="text" class="form-control mb-2" name="applicantName" placeholder="Applicant Name"
                            value="<?php echo setValue($applicant_name); ?>" <?php echo setReadonly($applicant_name); ?> />
                        <input type="text" class="form-control mb-2" name="applicantDesignation" placeholder="Designation"
                            value="<?php echo setValue($club_data['designation']); ?>" <?php echo setReadonly($club_data['designation']); ?> />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Moderator</label>
                        <input type="text" class="form-control mb-2" name="moderatorName" placeholder="Moderator Name"
                            value="<?php echo setValue($moderator_name); ?>" <?php echo setReadonly($moderator_name); ?> />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Noted by:</label>
                        <input type="text" class="form-control mb-2" name="dean_name" placeholder="College Dean Signature"
                            value="<?php echo setValue($dean_name); ?>" <?php echo setReadonly($dean_name); ?> />
                    </div>
                </div>

                <!-- Final Submit -->
                <div class="text-center">
                    <button type="submit" class="btn btn-success">Submit Proposal</button>
                </div>
            </div>
        </form>
    </div>

    <!-- ********************************************* -->
    <!-- Step 3: Pass PHP Data to JavaScript & Include External JS -->
    <!-- ********************************************* -->

    <script>
        // Pass the facilities array from PHP to JavaScript as a global variable.
        const facilitiesData = <?php echo json_encode($facilities); ?>;
    </script>

    <!-- Include the external JavaScript file -->
    <script src="js/activityProposal.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add this JavaScript before your existing scripts
        document.addEventListener('DOMContentLoaded', function() {
            const backButton = document.querySelector('.back-button');
            const backButtonInitialOffset = backButton.offsetTop;

            window.addEventListener('scroll', function() {
                if (window.pageYOffset > backButtonInitialOffset) {
                    backButton.classList.add('fixed');
                } else {
                    backButton.classList.remove('fixed');
                }
            });
        });

        // ... rest of your existing scripts ...

        // Add this to your existing JavaScript
        function updateTimeDisplay(input) {
            if (input.value) {
                const timeDisplay = input.nextElementSibling;
                const [hours, minutes] = input.value.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const hour12 = hour % 12 || 12;
                timeDisplay.textContent = `${hour12}:${minutes} ${ampm}`;
            }
        }

        // Initialize time displays
        document.querySelectorAll('.time-input').forEach(updateTimeDisplay);

        function updateTimeSlotDates() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;

            // Get all time slot date inputs
            const dateInputs = document.querySelectorAll('.timeslot-date');

            dateInputs.forEach(input => {
                // Set min and max dates
                input.min = startDate;
                input.max = endDate;

                // If current value is outside new range, clear it
                if (input.value) {
                    const currentDate = input.value;
                    if (currentDate < startDate || currentDate > endDate) {
                        input.value = '';
                    }
                }
            });

            // Add conflict check
            checkDateConflicts();
        }

        // Update your existing updateTimeSlotDates function
        function updateTimeSlotDates() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;

            // Existing time slot update code
            // ... existing code ...

            // Add conflict check
            checkDateConflicts();
        }

        async function checkDateConflicts() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;

            if (!startDate || !endDate) return;

            try {
                const response = await fetch('ajax/check_date_conflicts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        startDate,
                        endDate
                    })
                });

                const data = await response.json();
                const conflictsContainer = document.getElementById('date-conflicts');
                const conflictsList = document.getElementById('conflicts-list');

                if (data.conflicts && data.conflicts.length > 0) {
                    let conflictsHtml = '<ul class="conflicts-list">';

                    // Group conflicts by type
                    const proposals = data.conflicts.filter(c => c.type === 'proposal');
                    const bookings = data.conflicts.filter(c => c.type === 'booking');

                    // Show existing proposals first
                    if (proposals.length > 0) {
                        conflictsHtml += '<li class="conflict-section"><h6>Existing Activities:</h6></li>';
                        proposals.forEach(conflict => {
                            conflictsHtml += `
                                <li class="conflict-item ${conflict.status.toLowerCase()}">
                                    <div class="conflict-header">
                                        <strong>${conflict.title}</strong>
                                        <span class="status-badge ${conflict.status.toLowerCase()}">
                                            ${conflict.status}
                                        </span>
                                    </div>
                                    <div class="conflict-details">
                                        <span class="club-name">by ${conflict.club_name}</span>
                                        <span class="date-range">
                                            <i class="fas fa-calendar"></i> 
                                            ${new Date(conflict.start_date).toLocaleDateString()} - 
                                            ${new Date(conflict.end_date).toLocaleDateString()}
                                        </span>
                                    </div>
                                </li>`;
                        });
                    }

                    // Then show user's bookings
                    if (bookings.length > 0) {
                        conflictsHtml += '<li class="conflict-section"><h6>Your Facility Bookings:</h6></li>';
                        bookings.forEach(booking => {
                            conflictsHtml += `
                                <li class="conflict-item booking">
                                    <div class="conflict-header">
                                        <strong>${booking.facility_name}</strong>
                                        <span class="status-badge ${booking.status.toLowerCase()}">
                                            ${booking.status}
                                        </span>
                                    </div>
                                    <div class="conflict-details">
                                        <span class="booking-info">
                                            <i class="fas fa-clock"></i> 
                                            ${new Date(booking.start_date).toLocaleDateString()} 
                                            ${booking.booking_time}
                                        </span>
                                        ${booking.room_numbers ? `
                                            <span class="room-info">
                                                <i class="fas fa-door-open"></i> 
                                                Room(s): ${booking.room_numbers}
                                            </span>
                                        ` : ''}
                                    </div>
                                </li>`;
                        });
                    }

                    conflictsHtml += '</ul>';
                    conflictsList.innerHTML = conflictsHtml;
                    conflictsContainer.style.display = 'block';
                } else {
                    conflictsContainer.style.display = 'none';
                }
            } catch (error) {
                console.error('Error checking conflicts:', error);
            }
        }

        // Update the addSlot function to apply date restrictions to new slots
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('addSlot')) {
                // Wait for new slot to be added
                setTimeout(() => {
                    updateTimeSlotDates();
                }, 100);
            }
        });

        // Initial update
        updateTimeSlotDates();
    </script>

    <style>
        .conflicts-container {
            border-radius: 8px;
            overflow: hidden;
        }

        .conflicts-list {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0 0;
        }

        .conflict-item {
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .date-range,
        .facilities {
            font-size: 0.9rem;
            color: #666;
            display: inline-block;
            margin-top: 0.25rem;
        }

        .date-range i,
        .facilities i {
            margin-right: 0.35rem;
        }

        .conflict-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            border-left: 4px solid transparent;
        }

        .conflict-item.confirmed {
            border-left-color: #28a745;
        }

        .conflict-item.pending {
            border-left-color: #ffc107;
        }

        .conflict-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .status-badge.confirmed {
            background: #e8f5e9;
            color: #28a745;
        }

        .status-badge.pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .conflict-details {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.9rem;
            color: #666;
        }

        .club-name {
            font-weight: 500;
            color: #444;
        }

        .conflict-bookings {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #fff;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.9rem;
            white-space: pre-line;
        }

        .conflict-user {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
        }

        .conflict-section {
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }

        .conflict-section h6 {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .booking .conflict-header strong {
            color: #1976d2;
        }

        .booking-info,
        .room-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</body>

</html>
<?php
$conn->close();
?>
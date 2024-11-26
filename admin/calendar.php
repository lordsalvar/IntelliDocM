<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Calendar</title>

    <link rel="stylesheet" href="../css/calendar.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
    <!-- Header -->
    <?php include '../includes/navbar.php' ?>
    <!-- Sidebar Navigation -->

    <!-- Main Content for Calendar -->
    <div class="content">
        <div class="calendar-container d-flex">
            <div style="flex: 3">
                <!-- Calendar Header for Navigation -->
                <div class="calendar-header">
                    <button
                        class="btn btn-secondary"
                        onclick="previousMonth()">
                        Previous
                    </button>
                    <h2 id="calendarMonthYear"></h2>
                    <button class="btn btn-secondary" onclick="nextMonth()">
                        Next
                    </button>
                </div>

                <!-- Month View -->
                <div id="monthView" class="calendar-grid">
                    <!-- Days of the week -->
                    <div class="day-cell font-weight-bold">Sun</div>
                    <div class="day-cell font-weight-bold">Mon</div>
                    <div class="day-cell font-weight-bold">Tue</div>
                    <div class="day-cell font-weight-bold">Wed</div>
                    <div class="day-cell font-weight-bold">Thu</div>
                    <div class="day-cell font-weight-bold">Fri</div>
                    <div class="day-cell font-weight-bold">Sat</div>
                </div>

                <!-- Day View -->
                <div id="dayView" style="display: none">
                    <h3 id="selectedDate">Activities for</h3>
                    <ul class="activity-list list-group">
                        <!-- Example activities (Replace with dynamic content) -->
                    </ul>
                    <div class="back-button" onclick="showMonthView()">
                        Back to Month View
                    </div>
                </div>
            </div>

            <!-- Side Table for Approved Events -->
            <div style="flex: 1; margin-left: 20px">
                <h3>Approved Events</h3>
                <button
                    class="btn btn-primary btn-block"
                    onclick="openAddEventModal()">
                    Add New Event
                </button>
                <ul id="approvedEventList" class="list-group mt-3">
                    <!-- Approved events will be dynamically loaded here -->
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php' ?>

    <!-- Add Event Modal -->
    <div
        class="modal fade"
        id="addEventModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="addEventModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEventModalLabel">
                        Add New Event
                    </h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addEventForm">
                        <div class="form-group">
                            <label for="eventName">Event Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="eventName"
                                required />
                        </div>
                        <div class="form-group">
                            <label for="eventDate">Date</label>
                            <input
                                type="date"
                                class="form-control"
                                id="eventDate"
                                required />
                        </div>
                        <div class="form-group">
                            <label for="eventDescription">Description</label>
                            <textarea
                                class="form-control"
                                id="eventDescription"
                                rows="3"></textarea>
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="addEvent()">
                            Add Event
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Proposal Form Modal -->
    <div
        class="modal fade"
        id="activityProposalModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="activityProposalModalLabel"
        aria-hidden="true">
        <div
            class="modal-dialog modal-dialog-centered modal-lg"
            role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activityProposalModalLabel">
                        Activity Proposal Forms
                    </h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Date Submitted</th>
                                <th>Club</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample Row -->
                            <tr>
                                <td>Sports Fest Proposal</td>
                                <td>2024-11-15</td>
                                <td>Sports Club</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-info btn-sm">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Art Exhibit Proposal</td>
                                <td>2024-11-14</td>
                                <td>Art Club</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-info btn-sm">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Form Modal -->
    <div
        class="modal fade"
        id="bookingFormModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="bookingFormModalLabel"
        aria-hidden="true">
        <div
            class="modal-dialog modal-dialog-centered modal-lg"
            role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingFormModalLabel">
                        Booking Forms
                    </h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Date Submitted</th>
                                <th>Club</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample Row -->
                            <tr>
                                <td>Auditorium Booking</td>
                                <td>2024-11-13</td>
                                <td>Drama Club</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-info btn-sm">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Sports Field Booking</td>
                                <td>2024-11-12</td>
                                <td>Sports Club</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-info btn-sm">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        // Only approved events are stored here (replace this sample data with actual approved events from a database)
        const approvedEvents = {
            "2024-11-01": [{
                name: "Sports Fest",
                description: "Sports festival",
            }, ],
            "2024-11-15": [{
                name: "Art Exhibit",
                description: "Exhibition of student artworks",
            }, ],
        };

        function updateCalendar() {
            const monthYearDisplay =
                document.getElementById("calendarMonthYear");
            const monthNames = [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December",
            ];

            // Update month and year display
            monthYearDisplay.textContent = `${monthNames[currentMonth]} ${currentYear}`;

            // Calculate the number of days in the current month
            const daysInMonth = new Date(
                currentYear,
                currentMonth + 1,
                0
            ).getDate();
            const firstDay = new Date(
                currentYear,
                currentMonth,
                1
            ).getDay();

            // Clear previous calendar days
            const monthView = document.getElementById("monthView");
            monthView.innerHTML = `
            <div class="day-cell font-weight-bold">Sun</div>
            <div class="day-cell font-weight-bold">Mon</div>
            <div class="day-cell font-weight-bold">Tue</div>
            <div class="day-cell font-weight-bold">Wed</div>
            <div class="day-cell font-weight-bold">Thu</div>
            <div class="day-cell font-weight-bold">Fri</div>
            <div class="day-cell font-weight-bold">Sat</div>
        `;

            // Add empty cells for days before the start of the month
            for (let i = 0; i < firstDay; i++) {
                const emptyCell = document.createElement("div");
                emptyCell.classList.add("day-cell");
                monthView.appendChild(emptyCell);
            }

            // Populate the calendar with days and activity counts
            for (let day = 1; day <= daysInMonth; day++) {
                const date = `${currentYear}-${String(
                        currentMonth + 1
                    ).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                const dayCell = document.createElement("div");
                dayCell.classList.add("day-cell");
                dayCell.innerHTML = day;

                // Check if there are approved events for the date
                if (approvedEvents[date]) {
                    dayCell.innerHTML += `<br><span class="badge badge-info">${approvedEvents[date].length} Approved</span>`;
                }

                // Make day clickable to view activities
                dayCell.onclick = () => showDayView(date);
                monthView.appendChild(dayCell);
            }

            // Update side table with approved events for the month
            updateApprovedEventList();
        }

        function previousMonth() {
            if (currentMonth === 0) {
                currentMonth = 11;
                currentYear--;
            } else {
                currentMonth--;
            }
            updateCalendar();
        }

        function nextMonth() {
            if (currentMonth === 11) {
                currentMonth = 0;
                currentYear++;
            } else {
                currentMonth++;
            }
            updateCalendar();
        }

        function showDayView(date) {
            document.getElementById("monthView").style.display = "none";
            document.getElementById("dayView").style.display = "block";
            document.getElementById("selectedDate").innerText =
                "Activities for " + date;

            const activityList = document.querySelector(".activity-list");
            activityList.innerHTML = "";
            if (approvedEvents[date]) {
                approvedEvents[date].forEach((event) => {
                    const listItem = document.createElement("li");
                    listItem.className = "list-group-item";
                    listItem.textContent =
                        event.name + " - " + event.description;
                    activityList.appendChild(listItem);
                });
            } else {
                const listItem = document.createElement("li");
                listItem.className = "list-group-item";
                listItem.textContent = "No approved events for this day";
                activityList.appendChild(listItem);
            }

            // Update side table with day-view events
            updateApprovedEventList(date);
        }

        function showMonthView() {
            document.getElementById("monthView").style.display = "grid";
            document.getElementById("dayView").style.display = "none";
            updateApprovedEventList();
        }

        function openAddEventModal() {
            $("#addEventModal").modal("show");
        }

        function addEvent() {
            const eventName = document.getElementById("eventName").value;
            const eventDate = document.getElementById("eventDate").value;
            const eventDescription =
                document.getElementById("eventDescription").value;

            if (!eventName || !eventDate) {
                alert("Please enter both an event name and date.");
                return;
            }

            // Add event to approved events data
            if (!approvedEvents[eventDate]) {
                approvedEvents[eventDate] = [];
            }
            approvedEvents[eventDate].push({
                name: eventName,
                description: eventDescription,
            });

            // Close the modal and reset form
            $("#addEventModal").modal("hide");
            document.getElementById("addEventForm").reset();

            // Refresh the calendar and side table
            updateCalendar();
        }

        function updateApprovedEventList(selectedDate = null) {
            const eventList = document.getElementById("approvedEventList");
            eventList.innerHTML = ""; // Clear existing events

            const dates = selectedDate ? [selectedDate] :
                Object.keys(approvedEvents).filter((date) =>
                    date.startsWith(
                        `${currentYear}-${String(
                                  currentMonth + 1
                              ).padStart(2, "0")}`
                    )
                );
            dates.forEach((date) => {
                approvedEvents[date].forEach((event) => {
                    const listItem = document.createElement("li");
                    listItem.className = "list-group-item";
                    listItem.innerHTML = `<strong>${date}</strong>: ${event.name}`;
                    eventList.appendChild(listItem);
                });
            });
        }

        // Initialize the calendar on page load
        updateCalendar();
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
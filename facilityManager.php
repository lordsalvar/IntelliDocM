<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Detailed Facility Usage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/facilityManager.css">

    <style>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <?php include 'includes/clientnavbar.php'; ?>
    </header>
    <div class="container mt-5">
        <h1 class="text-center">Detailed Facility Usage Dashboard</h1>
        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <select id="facilitySelector" class="form-select mb-3" onchange="updateChart()">
                    <option value="" disabled selected>Select venue</option>
                    <option value="Ladouix Hall">Ladouix Hall</option>
                    <option value="Boulay Bldg.">Boulay Bldg.</option>
                    <option value="Gymnasium">Gymnasium</option>
                    <option value="Misereor Bldg.">Misereor Bldg.</option>
                    <option value="Polycarp Bldg.">Polycarp Bldg.</option>
                    <option value="Coinindre Bldg.">Coinindre Bldg.</option>
                    <option value="Piazza">Piazza</option>
                    <option value="Xavier Hall">Xavier Hall</option>
                    <option value="Open Court w/ Lights">Open Court w/ Lights</option>
                    <option value="IVET">IVET</option>
                    <option value="Nursing Room/Hall">Nursing Room/Hall</option>
                    <option value="Coindre Bldg.">Coindre Bldg.</option>
                    <option value="PowerCampus">PowerCampus</option>
                    <option value="Camp Raymond Bldg.">Camp Raymond Bldg.</option>
                    <option value="Norbert Bldg.">Norbert Bldg.</option>
                    <option value="H.E Hall">H.E Hall</option>
                    <option value="Atrium">Atrium</option>
                </select>
                <canvas id="facilityUsageChart"></canvas>
            </div>
            <div class="col-md-4">
                <h3 class="text-center">Monthly Reports</h3>
                <div id="reportList" class="list-group">
                    <!-- Dynamic report entries will be added here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Report Details Modal -->
    <div class="modal fade" id="reportDetailsModal" tabindex="-1" aria-labelledby="reportDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportDetailsModalLabel">Report Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="reportDetails"></p> <!-- Report details will be injected here -->
                </div>
            </div>
        </div>
    </div>
    <footer>
        <?php include 'includes/footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ctx = document.getElementById('facilityUsageChart').getContext('2d');
        let facilityUsageChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    label: 'Component Usage',
                    data: [],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FFCD56', '#4BC0C1', '#36A2EA', '#FF6385',
                        '#C9CBFF', '#FFC0CB', '#B0E0E6', '#800080', '#FF4500'
                    ],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
            }
        });

        function updateChart() {
            const selectedFacility = document.getElementById('facilitySelector').value;
            const components = {
                "Ladouix Hall": {"Chairs": 300, "Tables": 50, "Projectors": 2},
                "Boulay Bldg.": {"Computers": 25, "Whiteboards": 3, "Printers": 2},
                "Gymnasium": {"Basketballs": 15, "Volleyballs": 10, "Gym Mats": 20},
                // More facilities with their components
            }[selectedFacility] || {};
            facilityUsageChart.data.labels = Object.keys(components);
            facilityUsageChart.data.datasets[0].data = Object.values(components);
            facilityUsageChart.update();
            updateReports(selectedFacility);
        }

        function updateReports(facility) {
            const reportList = document.getElementById('reportList');
            reportList.innerHTML = ''; // Clear existing entries
            const reports = {
                "Ladouix Hall": [
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    { date: "2025-01-10", time: "14:00", description: "Annual General Meeting" },
                    { date: "2025-01-15", time: "09:00", description: "Monthly Sales Presentation" },
                    // Add 15 detailed entries for each facility as per the request
                ],
                // Detailed entries for other facilities
            };
            const facilityReports = reports[facility] || [];
            facilityReports.forEach(report => {
                const entry = document.createElement('a');
                entry.className = 'list-group-item list-group-item-action';
                entry.textContent = `${report.description} - ${report.date} at ${report.time}`;
                entry.href = '#';
                entry.setAttribute('data-bs-toggle', 'modal');
                entry.setAttribute('data-bs-target', '#reportDetailsModal');
                entry.onclick = function() {
                    document.getElementById('reportDetails').textContent = `Details of ${report.description}: Held on ${report.date} at ${report.time}.`;
                };
                reportList.appendChild(entry);
            });
        }
    </script>
</body>
</html>

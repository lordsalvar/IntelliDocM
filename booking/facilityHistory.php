<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venue History</title>
    <link rel="stylesheet" href="../css/history.css">
</head>
<body>
    <div class="container">
        <h1>Venue History</h1>
        <form id="venueForm">
            <label for="venueSelect">Select venue:</label>
            <select id="venueSelect" onchange="displayHistory()">
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
        </form>
        <div id="historyDisplay">
            <!-- History will be displayed here -->
        </div>
    </div>
</body>
<script>
function displayHistory() {
    const venue = document.getElementById('venueSelect').value;
    const historyDisplay = document.getElementById('historyDisplay');
    let content = '<h2>History for ' + venue + ':</h2>';
    content += '<ul>';
    switch (venue) {
        case 'Ladouix Hall':
            content += '<li>Event: Graduation, Date: 2023-06-15</li>';
            content += '<li>Event: Seminar, Date: 2022-11-12</li>';
            content += '<li>Event: Workshop, Date: 2021-10-08</li>';
            break;
        case 'Boulay Bldg.':
            content += '<li>Event: Conference, Date: 2023-05-20</li>';
            content += '<li>Event: Lecture Series, Date: 2022-03-15</li>';
            break;
        // Add cases for each venue with a variety of dates
        default:
            content += '<li>No historical data available.</li>';
            break;
    }
    content += '</ul>';
    historyDisplay.innerHTML = content;
}
</script>
</html>

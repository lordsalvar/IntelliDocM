<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request for Use of School Facilities - Cor Jesu College</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/booking.css">

</head>

<body>

    <div class="container mt-5 content">
        <div class="header-container">
            <div class="header-logo">
                <img src="images/cjc logo.jpg" alt="CJC Logo" class="logo">
            </div>
            <div class="header-text">
                <h2>COR JESU COLLEGE, INC.</h2>
                <p>Sacred Heart Avenue, Digos City, Province of Davao del Sur, Philippines</p>
                <p>Tel: (082) 553-2433 local 101 | Fax: (082) 553-2333 | Website: <a href="http://www.cjc.edu.ph">www.cjc.edu.ph</a></p>
            </div>
        </div>

        <form>
            <!-- Requesting Party Information -->
            <div class="form-section">
                <h3>Requesting Party</h3>
                <div class="form-group">
                    <label>Nature of Department/Group/Organization</label>
                    <input type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label>Purpose of Request</label>
                    <input type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label>Date of Use</label>
                    <input type="date" class="form-control">
                </div>
                <div class="form-group">
                    <label>Time of Use</label>
                    <input type="time" class="form-control">
                </div>
            </div>

            <!-- Facilities Requested -->
            <div class="form-section">
                <h3>Facilities Requested (Please check)</h3>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Ladouix Hall" id="ladouix">
                    <label class="form-check-label" for="ladouix">Ladouix Hall</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Boulay Bldg." id="boulay">
                    <label class="form-check-label" for="boulay">Boulay Bldg.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Gymnasium" id="gym">
                    <label class="form-check-label" for="gym">Gymnasium</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Misereor Bldg." id="misereor">
                    <label class="form-check-label" for="misereor">Misereor Bldg.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Polycarp Bldg." id="polycarp">
                    <label class="form-check-label" for="polycarp">Polycarp Bldg.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Coinindre Bldg." id="coinindre">
                    <label class="form-check-label" for="coinindre">Coinindre Bldg.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Piazza" id="piazza">
                    <label class="form-check-label" for="piazza">Piazza</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Xavier Hall" id="xavier">
                    <label class="form-check-label" for="xavier">Xavier Hall</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Open Court w/ Lights" id="openCourt">
                    <label class="form-check-label" for="openCourt">Open Court w/ Lights</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="IVET" id="ivet">
                    <label class="form-check-label" for="ivet">IVET</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Nursing Room/Hall" id="nursing">
                    <label class="form-check-label" for="nursing">Nursing Room/Hall</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Coindre Bldg. (CR)" id="coindre">
                    <label class="form-check-label" for="coindre">Coindre Bldg.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Power Campus" id="powerCampus">
                    <label class="form-check-label" for="powerCampus">Power Campus</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Camp Raymond Bldg." id="campRaymond">
                    <label class="form-check-label" for="campRaymond">Camp Raymond Bldg.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Norbert Bldg." id="norbert">
                    <label class="form-check-label" for="norbert">Norbert Bldg.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="H.E Hall" id="h.e hall">
                    <label class="form-check-label" for="h.e hall">H.E Hall</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="Atrium" id="atrium">
                    <label class="form-check-label" for="atrium">Atrium</label>
                </div>
                <input type="text" name="Others" id="others" placeholder="others">
            </div>

            <!-- Approval Section -->
            <div class="form-section">
                <h3>Approval</h3>
                <div class="form-group">
                    <label>Requested by:</label>
                    <input type="text" class="form-control" placeholder="Printed Name & Signature">
                    <div>
                        <input type="text" class="form-control" placeholder="Designation">
                    </div>
                </div>
                <div class="form-group">
                    <label>Cleared by:</label>
                    <input type="text" class="form-control" placeholder="In-Charge of Security Matters">
                </div>
                <div class="signature-section">
                    <div class="signature-block">
                        <label>Approved by:</label>
                        <input type="text" class="form-control" placeholder="Student Services Center">
                    </div>
                    <div class="signature-block">
                        <label>Endorsed by:</label>
                        <input type="text" class="form-control" placeholder="Moderator / Office Head">
                    </div>
                    <div class="signature-block">
                        <label>Approved by:</label>
                        <input type="text" class="form-control" placeholder="Property Custodian">
                    </div>
                </div>
            </div>
        </form>

        <hr>
        <!-- Button Row -->
        <div class="form-row">
            <div class="col-md-6 text-right">
                <button type="submit" class="btn btn-success mb-3">Submit Proposal</button>
            </div>
            <div class="col-md-6">
                <a class="btn btn-secondary" href="studentActivities.php" role="button">Back</a>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>
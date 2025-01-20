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
    <div class="container mt-5">
        <div class="header-container text-center mb-4">
            <img src="images/cjc_logo.jpg" alt="CJC Logo" class="logo mb-3">
            <h2>COR JESU COLLEGE, INC.</h2>
            <p>Sacred Heart Avenue, Digos City, Province of Davao del Sur, Philippines</p>
            <p>Tel: (082) 553-2433 local 101 | Fax: (082) 553-2333 | Website: <a href="http://www.cjc.edu.ph">www.cjc.edu.ph</a></p>
        </div>

        <div class="text-center mb-4">
            <h4>REQUEST FOR THE USE OF SCHOOL FACILITIES</h4>
        </div>

        <form>
            <!-- Requesting Party Information -->
            <div class="form-section mb-4">
                <h3>Requesting Party</h3>
                <div class="row align-items-center mb-3">
                    <div class="col-md-3">
                        <label for="organization_nature">Nature of Department/Group/Organization</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" id="organization_nature" class="form-control" name="organization_nature">
                    </div>
                </div>
                <div class="row align-items-center mb-3">
                    <div class="col-md-3">
                        <label for="contact_number">Contact Number</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" id="contact_number" class="form-control" name="contact_number">
                    </div>
                </div>
                <div class="row align-items-center mb-3">
                    <div class="col-md-3">
                        <label for="purpose_request">Purpose of Request</label>
                    </div>
                    <div class="col-md-9">
                        <input type="text" id="purpose_request" class="form-control" name="purpose_request">
                    </div>
                </div>
                <div class="row align-items-center mb-3">
                    <div class="col-md-3">
                        <label for="date_of_use">Date of Use</label>
                    </div>
                    <div class="col-md-9">
                        <input type="date" id="date_of_use" class="form-control" name="date_of_use">
                    </div>
                </div>
            </div>
        </form>


        <!-- Facilities Requested -->
        <div class="form-section">
            <h3>Facilities Requested (Please check)</h3>
            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="ladouixHall" name="facilities[]" value="Ladouix Hall">
                            <label class="form-check-label" for="ladouixHall">Ladouix Hall</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="ladouix_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="ladouix_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Boulay Bldg.</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="gymnasium" name="facilities[]" value="Gymnasium">
                            <label class="form-check-label" for="gymnasium">Gymnasium</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="gymnasium_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="gymnasium_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Miserero Bldg.</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Polycarp Bldg.</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Coindre Bldg.</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Piazza</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Xavier Hall</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Open Court w/ Lights</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">ITVET</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Nursing Room/Hall</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Power Campus</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>


            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Camp Raymond Bldg.</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>


            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Norbert Bldg.</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">H.E Hall</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="boulayBldg" name="facilities[]" value="Boulay Bldg">
                            <label class="form-check-label" for="boulayBldg">Atrium</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Building or Room Number" name="boulay_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="boulay_time">
                    </div>
                </div>
            </div>
            <!-- Add similar blocks for all other facilities -->

            <div class="form-group">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="others" name="facilities[]" value="Others">
                            <label class="form-check-label" for="others">Others</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Specify Other Facility" name="others_building">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control" placeholder="Time of Use" name="others_time">
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Section -->
        <div class="form-section mt-5">
            <h3>Approval</h3>
            <div class="row mb-4 text-center">
                <div class="col mb-4">
                    <div class="form-group col">
                        <label>Requested by:</label>
                        <input type="text" class="form-control" placeholder="Printed Name & Signature" name="requested_by">
                        <div>
                            <input type="text" class="form-control mt-2" placeholder="Designation" name="requested_by_designation">
                        </div>
                    </div>
                </div>
                <div class="col mb-4">
                    <div class="form-group col">
                        <label>Cleared by:</label>
                        <input type="text" class="form-control" placeholder="Printed Name & Signature" name="cleared_by">
                        <div>
                            <input type="text" class="form-control mt-2" placeholder="Designation" name="cleared_by_designation">
                        </div>
                    </div>
                </div>
                <div class="col mb-4">
                    <div class="form-group col">
                        <label>Approved by:</label>
                        <input type="text" class="form-control" placeholder="Printed Name & Signature" name="approved_by">
                        <div>
                            <input type="text" class="form-control mt-2" placeholder="Designation" name="approved_by_designation">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 text-center">
                <div class="form-group col">
                    <label>Endorsed by:</label>
                    <input type="text" class="form-control" placeholder="Printed Name & Signature" name="endorsed_by">
                </div>
                <div class="form-group col">
                    <label>Approved by:</label>
                    <input type="text" class="form-control" placeholder="Property Custodian" name="approved_by_pc">
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="form-row mt-4">
            <div class="col-md-6 text-right">
                <button type="submit" class="btn btn-success mb-3">Submit Proposal</button>
            </div>
            <div class="col-md-6">
                <a class="btn btn-secondary" href="studentActivities.php" role="button">Back</a>
            </div>
        </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
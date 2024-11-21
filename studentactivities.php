<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Proposal Form</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .logo {
            max-width: 100px;
            /* Adjust the size as needed */
            display: block;
            margin: 0 auto 20px;
            /* Center the logo and add some margin */
        }

        table {
            margin-top: 20px;
            width: 100%;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #dc3545;
            /* Red background for headers */
            color: white;
            /* White text for contrast */
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <img src="images/cjc logo.jpg" alt="CJC Logo" class="logo">
        <h2 class="text-center">COR JESU COLLEGE, INC.</h2>
        <h4 class="text-center">Activity Proposal Form</h4>

        <form action="submit_activity_proposal.php" method="post">
            <table class="table table-bordered">
                <tr>
                    <td colspan="2">
                        <label for="organization_class">Name of the Organization/Class/College:</label>
                        <input type="text" class="form-control" id="organization_class" name="organization_class" required>
                    </td>
                    <td colspan="2">
                        <label for="activity_title">Title of the Activity:</label>
                        <input type="text" class="form-control" id="activity_title" name="activity_title" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label for="objectives">Objectives:</label>
                        <textarea class="form-control" id="objectives" name="objectives" rows="3" required></textarea>
                    </td>
                    <td colspan="2">
                        <label for="activity_date">Date of the Activity:</label>
                        <input type="date" class="form-control" id="activity_date" name="activity_date" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="start_time">Starting Time:</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </td>
                    <td>
                        <label for="finish_time">Finishing Time:</label>
                        <input type="time" class="form-control" id="finish_time" name="finish_time" required>
                    </td>
                    <td colspan="2">
                        <label for="venue">Venue of the Activity:</label>
                        <input type="text" class="form-control" id="venue" name="venue" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label for="category">Student Development Program Category:</label><br>
                        <input type="checkbox" id="omp" name="category[]" value="Organizational Management Development (OMP)">
                        <label for="omp">Organizational Management Development (OMP)</label><br>
                        <input type="checkbox" id="ksd" name="category[]" value="Knowledge & Skills Development (KSD)">
                        <label for="ksd">Knowledge & Skills Development (KSD)</label><br>
                        <input type="checkbox" id="srf" name="category[]" value="Spiritual & Religious Formation (SRF)">
                        <label for="srf">Spiritual & Religious Formation (SRF)</label><br>
                        <input type="checkbox" id="rpi" name="category[]" value="Research & Project Initiative (RPI)">
                        <label for="rpi">Research & Project Initiative (RPI)</label><br>
                        <input type="checkbox" id="ct" name="category[]" value="Capacity and Teambuilding (CT)">
                        <label for="ct">Capacity and Teambuilding (CT)</label><br>
                        <input type="checkbox" id="cesa" name="category[]" value="Community Engagement & Social Advocacy (CESA)">
                        <label for="cesa">Community Engagement & Social Advocacy (CESA)</label><br>
                        <input type="checkbox" id="others" name="category[]" value="Others">
                        <label for="others">Others (Please Specify)</label>
                        <input type="text" class="form-control" name="other_category" placeholder="Specify..." style="margin-top: 5px;">
                    </td>
                    <td colspan="2">
                        <label for="target_participants">Expected Number of Participants:</label>
                        <input type="number" class="form-control" id="target_participants" name="target_participants" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label for="applicant_name">Applicant:</label>
                        <input type="text" class="form-control" id="applicant_name" name="applicant_name" required>
                    </td>
                    <td colspan="2">
                        <label for="date_filed">Date Filed:</label>
                        <input type="date" class="form-control" id="date_filed" name="date_filed" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <label for="moderator_name">Moderator:</label>
                        <input type="text" class="form-control" id="moderator_name" name="moderator_name" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <label for="other_faculty">Other Faculty/Staff to Oversee the Activity:</label>
                        <input type="text" class="form-control" id="other_faculty" name="other_faculty" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <label for="college_dean">College Dean:</label>
                        <input type="text" class="form-control" id="college_dean" name="college_dean" value="College Dean Name" readonly>
                    </td>
                </tr>
            </table>

            <!-- Button alignment -->
            <div class="form-row">
                <div class="col-md-6">
                    <a href="student_dashboard.php" class="btn btn-secondary mb-3">Back</a>
                </div>
                <div class="col-md-6 text-right">
                    <button type="submit" class="btn btn-danger mb-3">Submit Proposal</button>
                </div>
                <div class="col-md-6 text-middle">
                    <a href="bookingform.php" class="btn btn-primary mb-3">Proceed to Booking Form</a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
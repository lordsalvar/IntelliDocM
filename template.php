<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Define the template and output filenames
    $templateContent = <<<EOD
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Activity Proposal Form</title>
    </head>
    <body>
        <div class="container mt-5">
            <h2 class="text-center">COR JESU COLLEGE, INC.</h2>
            <h4 class="text-center">Activity Proposal Form</h4>
            <table class="table table-bordered">
                <tr>
                    <td colspan="2"><strong>Name of the Organization/Class/College:</strong> {organization_class}</td>
                    <td colspan="2"><strong>Title of the Activity:</strong> {activity_title}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Objectives:</strong> {objectives}</td>
                    <td colspan="2"><strong>Date of the Activity:</strong> {activity_date}</td>
                </tr>
                <tr>
                    <td><strong>Starting Time:</strong> {start_time}</td>
                    <td><strong>Finishing Time:</strong> {finish_time}</td>
                    <td colspan="2"><strong>Venue:</strong> {venue}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Student Development Program Category:</strong> {category}</td>
                    <td colspan="2"><strong>Expected Number of Participants:</strong> {target_participants}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Applicant:</strong> {applicant_name}</td>
                    <td colspan="2"><strong>Date Filed:</strong> {date_filed}</td>
                </tr>
                <tr>
                    <td colspan="4"><strong>Moderator:</strong> {moderator_name}</td>
                </tr>
                <tr>
                    <td colspan="4"><strong>Other Faculty/Staff to Oversee the Activity:</strong> {other_faculty}</td>
                </tr>
                <tr>
                    <td colspan="4"><strong>College Dean:</strong> {college_dean}</td>
                </tr>
            </table>
        </div>
    </body>
    </html>
    EOD;

    // Collect form data and prepare replacement array
    $data = [
        'organization_class' => $_POST['organization_class'] ?? 'N/A',
        'activity_title' => $_POST['activity_title'] ?? 'N/A',
        'objectives' => $_POST['objectives'] ?? 'N/A',
        'activity_date' => $_POST['activity_date'] ?? 'N/A',
        'start_time' => $_POST['start_time'] ?? 'N/A',
        'finish_time' => $_POST['finish_time'] ?? 'N/A',
        'venue' => $_POST['venue'] ?? 'N/A',
        'category' => isset($_POST['category']) ? implode(', ', $_POST['category']) : 'N/A',
        'target_participants' => $_POST['target_participants'] ?? 'N/A',
        'applicant_name' => $_POST['applicant_name'] ?? 'N/A',
        'date_filed' => $_POST['date_filed'] ?? 'N/A',
        'moderator_name' => $_POST['moderator_name'] ?? 'N/A',
        'other_faculty' => $_POST['other_faculty'] ?? 'N/A',
        'college_dean' => $_POST['college_dean'] ?? 'College Dean Name'
    ];

    // Replace placeholders in template content
    foreach ($data as $placeholder => $value) {
        $templateContent = str_replace("{" . $placeholder . "}", $value, $templateContent);
    }

    // Save modified content as temporary HTML file
    $tempHtmlFile = 'temp_document.html';
    file_put_contents($tempHtmlFile, $templateContent);

    // Define output file name
    $outputFile = 'Activity_Proposal_' . time() . '.docx';
    $command = "libreoffice --headless --convert-to docx $tempHtmlFile --outdir .";

    // Run LibreOffice command to convert HTML to DOCX
    exec($command);

    // Serve the generated document for download
    if (file_exists($outputFile)) {
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$outputFile");
        header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
        header("Content-Length: " . filesize($outputFile));
        readfile($outputFile);

        // Cleanup temporary files
        unlink($tempHtmlFile);
        unlink($outputFile);
    } else {
        echo "Error: Document conversion failed.";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Activity Proposal Form</title>
</head>

<body>
    <form action="" method="post">
        <label>Name of the Organization/Class/College:</label>
        <input type="text" name="organization_class" required><br>

        <label>Title of the Activity:</label>
        <input type="text" name="activity_title" required><br>

        <label>Objectives:</label>
        <textarea name="objectives" rows="3" required></textarea><br>

        <label>Date of the Activity:</label>
        <input type="date" name="activity_date" required><br>

        <label>Starting Time:</label>
        <input type="time" name="start_time" required><br>

        <label>Finishing Time:</label>
        <input type="time" name="finish_time" required><br>

        <label>Venue:</label>
        <input type="text" name="venue" required><br>

        <label>Student Development Program Category:</label><br>
        <input type="checkbox" name="category[]" value="OMP"> OMP<br>
        <input type="checkbox" name="category[]" value="KSD"> KSD<br>
        <input type="checkbox" name="category[]" value="SRF"> SRF<br>
        <input type="checkbox" name="category[]" value="RPI"> RPI<br>
        <input type="checkbox" name="category[]" value="CT"> CT<br>
        <input type="checkbox" name="category[]" value="CESA"> CESA<br>

        <label>Expected Number of Participants:</label>
        <input type="number" name="target_participants" required><br>

        <label>Applicant:</label>
        <input type="text" name="applicant_name" required><br>

        <label>Date Filed:</label>
        <input type="date" name="date_filed" required><br>

        <label>Moderator:</label>
        <input type="text" name="moderator_name" required><br>

        <label>Other Faculty/Staff to Oversee the Activity:</label>
        <input type="text" name="other_faculty" required><br>

        <label>College Dean:</label>
        <input type="text" name="college_dean" value="College Dean Name" readonly><br>

        <button type="submit">Generate Document</button>
    </form>
</body>

</html>
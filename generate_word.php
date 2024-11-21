<?php
// Include the PHPWord library
require 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Load the Word template
    $templateProcessor = new TemplateProcessor('template.docx');

    // Replace text placeholders with submitted form data
    $templateProcessor->setValue('organization', $_POST['organization']);
    $templateProcessor->setValue('activity_title', $_POST['activity_title']);

    // Process Organization Category checkboxes
    $organizationCategory = isset($_POST['organization_category']) ? $_POST['organization_category'] : [];
    $templateProcessor->setValue('Academic', in_array('Academic', $organizationCategory) ? '✓' : '[ ]');
    $templateProcessor->setValue('Non-Academic', in_array('Non-Academic', $organizationCategory) ? '✓' : '[ ]');
    $templateProcessor->setValue('ACCO', in_array('ACCO', $organizationCategory) ? '✓' : '[ ]');
    $templateProcessor->setValue('CSG', in_array('CSG', $organizationCategory) ? '✓' : '[ ]');

    // Save the document as a new file and prompt for download
    $outputFile = 'Activity_Proposal_' . time() . '.docx';
    $templateProcessor->saveAs($outputFile);

    // Set headers to prompt download of the generated file
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$outputFile");
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    header("Content-Length: " . filesize($outputFile));
    flush();
    readfile($outputFile);
    unlink($outputFile); // delete the file after download
    exit;
}

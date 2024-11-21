<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;

// Create a new PHPWord instance
$phpWord = new PhpWord();

// Add a new section and some text
$section = $phpWord->addSection();
$section->addText("This is a test document.");
$section->addText("IT WEEK.");
$section->addText("Organization: Test Organization");
$section->addText("Activity Title: Test Activity");

// Save the document and prompt download
$outputFile = 'Test_Document_' . time() . '.docx';
$phpWord->save($outputFile, 'Word2007', true);

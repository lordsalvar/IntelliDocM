<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "migel";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getDbConnection()
{
    global $conn;
    return $conn;
}

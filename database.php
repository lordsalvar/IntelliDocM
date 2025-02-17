<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dbcb";

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

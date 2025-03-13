<?php

$servername = "127.0.0.1";
$username = "Keshav";
$password = "12345678";
$dbname = "student_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection Failed: ".$conn->connect_error);
} else {
    echo "Connected Successfully";
}

?>

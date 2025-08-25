<?php
$host = "";
$port = "";
$user = "";
$pass = "";
$dbname = "";

$conn = null;
if ($host !== "" && $user !== "" && $dbname !== "") {
    $conn = @new mysqli($host, $user, $pass, $dbname, $port);
    if ($conn->connect_error) {
        $conn = null;
    } else {
        $conn->set_charset("utf8mb4");
    }
}

<?php
$host = "";
$port = "";
$user = "";
$pass = "";
$dbname = "";

$conn = @new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_errno) {
    $conn = null;
} else {
    $conn->set_charset("utf8mb4");
}

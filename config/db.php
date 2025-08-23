<?php
$host = "172.17.0.4";
$port = "30701";
$user = "ezk";
$pass = "dlwlzpdl";
$dbname = "tdems";

$conn = @new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_errno) {
    $conn = null;
} else {
    $conn->set_charset("utf8mb4");
}

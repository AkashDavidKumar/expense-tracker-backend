<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "expense_tracker";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");
?>

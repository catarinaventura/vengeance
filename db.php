<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gaming_events";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de ligação: " . $conn->connect_error);
}

session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>

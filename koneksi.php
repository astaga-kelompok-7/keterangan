<?php
$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "tahu_kriuk";
$port = 33061;

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>

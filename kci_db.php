<?php
if (getenv('DB_HOST')) {
    // ONLINE - Aiven on Render
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db   = getenv('DB_NAME');
    $port = getenv('DB_PORT');
    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, __DIR__ . '/ca.pem', NULL, NULL);
    mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    // LOCAL - Your WAMP
    $conn = mysqli_connect("localhost", "root", "", "kingdomite");
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
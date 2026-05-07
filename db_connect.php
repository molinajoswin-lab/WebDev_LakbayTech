<?php
$host = "localhost";
$dbname = "lakbay";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log error instead of showing it to users
    error_log("Database connection error: " . $e->getMessage());

    // Show generic message only
    die("Something went wrong. Please try again later.");
}
?>
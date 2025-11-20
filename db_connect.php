<?php
// db_connect.php
$host = "localhost";
$db   = "devvest";
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // PDO object for investors
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    
    error_log("Investor DB connection failed: " . $e->getMessage());
    die("Database connection failed.");
}
?>

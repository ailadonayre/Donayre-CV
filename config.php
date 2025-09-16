<?php
$host = "localhost";
$port = "5432";
$dbname = "donayre_cv";
$user = "arcd";
$password = "leeyushi";

try {
    $db = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to PostgreSQL.";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
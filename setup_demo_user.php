<?php
// setup_demo_user.php - Run this once to create demo user
require_once 'auth.php';

echo "<h2>Setting up demo user...</h2>";

// Create demo user
$result = registerUser('demo', 'demo@test.com', 'demo123');

if ($result['success']) {
    echo "<p style='color: green;'>✓ Demo user created successfully!</p>";
    echo "<p>Username: <strong>demo</strong></p>";
    echo "<p>Email: <strong>demo@test.com</strong></p>";
    echo "<p>Password: <strong>demo123</strong></p>";
} else {
    echo "<p style='color: red;'>✗ Error: " . $result['message'] . "</p>";
}

echo "<p><a href='login.php'>Go to Login Page</a></p>";

// Also create users table if it doesn't exist
try {
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    );

    CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
    CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
    ";
    
    $db->exec($sql);
    echo "<p style='color: blue;'>✓ Database tables verified/created.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database setup error: " . $e->getMessage() . "</p>";
}
?>
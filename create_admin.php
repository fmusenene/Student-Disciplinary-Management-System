<?php
require_once 'config/database.php';

// Delete existing admin user if exists
$stmt = $conn->prepare("DELETE FROM users WHERE username = 'admin'");
$stmt->execute();

// Create new admin user
$username = 'admin';
$email = 'admin@school.com';
$full_name = 'System Administrator';
$role = 'admin';
$password = 'admin123'; // This will be the password you use to login
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (username, email, full_name, role, password)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([$username, $email, $full_name, $role, $hashed_password]);

echo "Admin user created successfully!<br>";
echo "Username: admin<br>";
echo "Password: admin123<br>";
echo "<a href='login.php'>Go to Login</a>";
?> 
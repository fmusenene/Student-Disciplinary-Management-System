<?php
require_once __DIR__ . '/../config/database.php';

// Function to execute SQL file
function executeSqlFile($conn, $file) {
    $sql = file_get_contents($file);
    try {
        $conn->exec($sql);
        echo "Successfully executed $file\n";
    } catch (PDOException $e) {
        echo "Error executing $file: " . $e->getMessage() . "\n";
    }
}

// Execute SQL files
$sqlFiles = [
    __DIR__ . '/student_parent.sql'
];

foreach ($sqlFiles as $file) {
    executeSqlFile($conn, $file);
}

echo "Database setup completed.\n";
?> 
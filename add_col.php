<?php
require 'config.php';

try {
    $pdo->exec("ALTER TABLE employees ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    echo "Column is_archived added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column is_archived already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>

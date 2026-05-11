<?php
require 'c:\xampp\htdocs\SugarlandiaDB\config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reservations (
            reservation_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20) NOT NULL,
            customer_email VARCHAR(100),
            pickup_date DATE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS reservation_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            reservation_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(product_id)
        );
    ");
    echo "Tables created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

<?php
/**
 * Database Connection (MySQLi)
 * For use with Player API endpoints
 */

require_once __DIR__ . '/../config/config.php';

// Create mysqli connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed");
}

// Set charset
$conn->set_charset(DB_CHARSET);

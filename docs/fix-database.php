<?php
/**
 * Database Schema Fix Script
 * 
 * This script checks and fixes the events table schema to prevent data truncation errors.
 * It should be run once to update the event_type column to allow longer values.
 */

// Include database configuration
require_once '../includes/config.php';
require_once '../includes/db.php';

// Function to check if a column needs to be modified
function checkColumnLength($db, $table, $column, $requiredLength) {
    $query = "SELECT CHARACTER_MAXIMUM_LENGTH 
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND COLUMN_NAME = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $currentLength = $row['CHARACTER_MAXIMUM_LENGTH'];
        echo "Current length of {$table}.{$column}: {$currentLength}\n";
        return $currentLength < $requiredLength;
    }
    
    echo "Column {$table}.{$column} not found\n";
    return false;
}

// Function to modify column length
function modifyColumnLength($db, $table, $column, $newLength) {
    $query = "ALTER TABLE {$table} MODIFY COLUMN {$column} VARCHAR({$newLength}) NOT NULL";
    
    if ($db->query($query)) {
        echo "Successfully modified {$table}.{$column} to VARCHAR({$newLength})\n";
        return true;
    } else {
        echo "Error modifying column: " . $db->error . "\n";
        return false;
    }
}

// Connect to database
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    
    echo "Connected to database successfully\n";
    
    // Check if events table exists
    $result = $db->query("SHOW TABLES LIKE 'events'");
    if ($result->num_rows == 0) {
        echo "Events table does not exist. No action needed.\n";
        exit;
    }
    
    // Check event_type column length
    if (checkColumnLength($db, 'events', 'event_type', 20)) {
        echo "Column length is insufficient. Modifying...\n";
        modifyColumnLength($db, 'events', 'event_type', 20);
    } else {
        echo "Column length is sufficient. No action needed.\n";
    }
    
    // Optional: Check for invalid data
    $result = $db->query("SELECT DISTINCT event_type FROM events");
    echo "Current event types in database:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['event_type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($db) && !$db->connect_error) {
        $db->close();
        echo "Database connection closed\n";
    }
}

echo "Script completed\n";

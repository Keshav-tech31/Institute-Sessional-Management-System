<?php

// Add these lines at the very top of your PHP file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Rest of your code...
// Configuration
$servername = "localhost";
$username = "root";
$password = "9528503294";
$dbname = "student_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Prepare response array
$response = array();

// Check connection
if ($conn->connect_error) {
    $response['status'] = 'error';
    $response['message'] = "Connection failed: " . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// Initialize array to store students
$response['students'] = array();

try {
    // Get filter parameters
    $branch_filter = isset($_GET['branch']) ? $_GET['branch'] : null;
    $semester_filter = isset($_GET['semester']) ? $_GET['semester'] : null;
    
    // Determine which tables to query based on semester filter
    $tables = array();
    if ($semester_filter && $semester_filter !== 'all') {
        $tables[] = "semester_" . $semester_filter;
    } else {
        $tables = array('semester_III', 'semester_IV', 'semester_V', 'semester_VI');
    }
    
    // Loop through each table and get data
    foreach ($tables as $table) {
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows == 0) {
            continue; // Skip if table doesn't exist
        }
        
        // Determine semester name from table
        $semester = str_replace('semester_', '', $table);
        
        // Build query based on filters
        $query = "SELECT student_name, branch_name, '$semester' as semester, roll_no, dob FROM $table";
        $where_conditions = array();
        
        // Add branch filter if specified
        if ($branch_filter && $branch_filter !== 'all') {
            $where_conditions[] = "branch_name = ?";
        }
        
        // Add WHERE clause if any conditions exist
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        // Bind parameters if any conditions exist
        if ($branch_filter && $branch_filter !== 'all') {
            $stmt->bind_param("s", $branch_filter);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add students from this table to results
        while ($row = $result->fetch_assoc()) {
            $response['students'][] = $row;
        }
        
        $stmt->close();
    }
    
    // Sort all students by roll number
    usort($response['students'], function($a, $b) {
        return $a['roll_no'] <=> $b['roll_no'];
    });
    
    // Set success status
    $response['status'] = 'success';
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = "Error: " . $e->getMessage();
}

// Close connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
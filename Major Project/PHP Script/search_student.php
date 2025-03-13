<?php
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
    // Get search parameters
    $name_search = isset($_GET['name']) ? $_GET['name'] : null;
    $roll_search = isset($_GET['roll_no']) ? $_GET['roll_no'] : null;
    $branch_search = isset($_GET['branch']) ? $_GET['branch'] : null;
    $semester_search = isset($_GET['semester']) ? $_GET['semester'] : null;
    
    // Determine which tables to query based on semester filter
    $tables = array();
    if ($semester_search && $semester_search !== '') {
        $tables[] = "semester_" . $semester_search;
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
        
        // Build query based on search parameters
        $query = "SELECT student_name, branch_name, '$semester' as semester, roll_no, dob FROM $table WHERE 1=1";
        $params = array();
        $types = "";
        
        // Add name search if specified
        if ($name_search) {
            $query .= " AND student_name LIKE ?";
            $name_param = "%" . $name_search . "%";
            $params[] = &$name_param;
            $types .= "s";
        }
        
        // Add roll number search if specified
        if ($roll_search) {
            $query .= " AND roll_no LIKE ?";
            $roll_param = "%" . $roll_search . "%";
            $params[] = &$roll_param;
            $types .= "s";
        }
        
        // Add branch search if specified
        if ($branch_search && $branch_search !== '') {
            $query .= " AND branch_name = ?";
            $params[] = &$branch_search;
            $types .= "s";
        }
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        
        // Bind parameters if any
        if (!empty($params)) {
            // Create array with $types as first element followed by $params
            $bind_params = array($types);
            foreach ($params as $param) {
                $bind_params[] = $param;
            }
            
            // Call bind_param with the array elements as separate parameters
            call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Add students from this table to results
        while ($row = $result->fetch_assoc()) {
            $response['students'][] = $row;
        }
        
        $stmt->close();
    }
    
    // Set success status even if no results are found
    $response['status'] = 'success';
    
    // If no students found, set empty array and message
    if (empty($response['students'])) {
        $response['message'] = "No students found matching the search criteria.";
    } else {
        $response['message'] = count($response['students']) . " student(s) found.";
        
        // Sort all students by roll number
        usort($response['students'], function($a, $b) {
            return $a['roll_no'] <=> $b['roll_no'];
        });
    }
    
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
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

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_name = $_POST['student_name'];
    $branch_name = $_POST['branch_name'];
    $semester = $_POST['semester'];
    $roll_no = $_POST['roll_no'];
    $dob = $_POST['dob'];

    // Determine the table based on semester
    switch ($semester) {
        case 'III':
            $table_name = 'semester_III';
            break;
        case 'IV':
            $table_name = 'semester_IV';
            break;
        case 'V':
            $table_name = 'semester_V';
            break;
        case 'VI':
            $table_name = 'semester_VI';
            break;
        default:
            $response['status'] = 'error';
            $response['message'] = "Invalid semester selected.";
            echo json_encode($response);
            exit;
    }

    // First check if roll number already exists in any semester table
    $tables = array('semester_III', 'semester_IV', 'semester_V', 'semester_VI');
    $rollExists = false;
    
    foreach ($tables as $table) {
        // Make sure table exists before querying
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            $check_sql = "SELECT roll_no FROM $table WHERE roll_no = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $roll_no);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $rollExists = true;
                break;
            }
            $check_stmt->close();
        }
    }
    
    // If roll number already exists, return error
    if ($rollExists) {
        $response['status'] = 'error';
        $response['message'] = "Roll number '$roll_no' already exists. Please use a different roll number.";
        echo json_encode($response);
        exit;
    }

    // If roll number is unique, proceed with insertion
    $sql = "INSERT INTO $table_name (student_name, branch_name, roll_no, dob) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $student_name, $branch_name, $roll_no, $dob);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = "Student added successfully.";
    } else {
        $response['status'] = 'error';
        $response['message'] = "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
    
    // Return JSON response
    echo json_encode($response);
}

// Add AJAX endpoint to check roll number availability
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['check_roll'])) {
    $roll_no = $_GET['check_roll'];
    
    // Check all semester tables
    $tables = array('semester_III', 'semester_IV', 'semester_V', 'semester_VI');
    $rollExists = false;
    
    foreach ($tables as $table) {
        // Make sure table exists before querying
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            $check_sql = "SELECT roll_no FROM $table WHERE roll_no = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $roll_no);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $rollExists = true;
                break;
            }
            $check_stmt->close();
        }
    }
    
    $response = array(
        'status' => $rollExists ? 'error' : 'success',
        'message' => $rollExists ? 'Roll number already exists' : 'Roll number is available',
        'available' => !$rollExists
    );
    
    echo json_encode($response);
    exit;
}
?>
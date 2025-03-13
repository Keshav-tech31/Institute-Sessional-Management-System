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

// Search for student by roll number
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_roll'])) {
    $roll_no = $_GET['search_roll'];
    
    // Check all semester tables
    $tables = array('semester_III', 'semester_IV', 'semester_V', 'semester_VI');
    $student_found = false;
    $student_data = null;
    $found_table = null;
    
    foreach ($tables as $table) {
        // Make sure table exists before querying
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            $search_sql = "SELECT * FROM $table WHERE roll_no = ?";
            $search_stmt = $conn->prepare($search_sql);
            $search_stmt->bind_param("s", $roll_no);
            $search_stmt->execute();
            $search_result = $search_stmt->get_result();
            
            if ($search_result->num_rows > 0) {
                $student_found = true;
                $student_data = $search_result->fetch_assoc();
                $found_table = $table;
                break;
            }
            $search_stmt->close();
        }
    }
    
    if ($student_found) {
        $response = array(
            'status' => 'success',
            'message' => 'Student found',
            'student' => $student_data,
            'table' => $found_table
        );
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Student not found'
        );
    }
    
    echo json_encode($response);
    exit;
}

// Delete student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $roll_no = $_POST['roll_no'];
    $table = $_POST['table'];
    
    // Validate table name for security (only allow semester_III, semester_IV, etc.)
    if (!in_array($table, array('semester_III', 'semester_IV', 'semester_V', 'semester_VI'))) {
        $response['status'] = 'error';
        $response['message'] = "Invalid table name.";
        echo json_encode($response);
        exit;
    }
    
    // Check if student exists in the specified table
    $check_sql = "SELECT * FROM $table WHERE roll_no = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $roll_no);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $response['status'] = 'error';
        $response['message'] = "Student not found in the database.";
        echo json_encode($response);
        exit;
    }
    $check_stmt->close();
    
    // Delete the student
    $delete_sql = "DELETE FROM $table WHERE roll_no = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("s", $roll_no);
    
    if ($delete_stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = "Student deleted successfully.";
    } else {
        $response['status'] = 'error';
        $response['message'] = "Error deleting student: " . $conn->error;
    }
    
    $delete_stmt->close();
    $conn->close();
    
    // Return JSON response
    echo json_encode($response);
    exit;
}
?>
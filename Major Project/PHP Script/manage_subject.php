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

// Function to create semester-specific subject table if it doesn't exist
function createSemesterSubjectTable($conn, $semester) {
    $table_name = "sem{$semester}_subjects";
    
    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows == 0) {
        $create_table = "CREATE TABLE $table_name (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject_name VARCHAR(255) NOT NULL,
            subject_code VARCHAR(30) NOT NULL UNIQUE,
            subject_type ENUM('THEORY_PRACTICAL', 'THEORY_ONLY', 'PRACTICAL_ONLY') NOT NULL,
            credits INT(2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if (!$conn->query($create_table)) {
            return false;
        }
    }
    return true;
}

// Check if request method is GET and handling AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Check subject code availability
    if (isset($_GET['check_code'])) {
        $subject_code = $_GET['check_code'];
        
        // Check across all semester tables
        $semesters = ['III', 'IV', 'V', 'VI'];
        $is_available = true;
        
        foreach ($semesters as $semester) {
            $table_name = "sem{$semester}_subjects";
            
            // Ensure table exists
            createSemesterSubjectTable($conn, $semester);
            
            $check_sql = "SELECT subject_code FROM $table_name WHERE subject_code = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $subject_code);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $is_available = false;
                break;
            }
            $check_stmt->close();
        }
        
        $response = array(
            'status' => $is_available ? 'success' : 'error',
            'message' => $is_available ? 'Subject code is available' : 'Subject code already exists',
            'available' => $is_available
        );
        
        echo json_encode($response);
        exit;
    }
    
    // List subjects
    if (isset($_GET['action']) && $_GET['action'] == 'list') {
        $semester = isset($_GET['semester']) ? $_GET['semester'] : 'all';
        $subject_type = isset($_GET['subject_type']) ? $_GET['subject_type'] : 'all';
        
        $subjects = array();
        
        // Check which semesters to query
        $semesters_to_check = ($semester == 'all') 
            ? ['III', 'IV', 'V', 'VI'] 
            : [$semester];
        
        foreach ($semesters_to_check as $curr_semester) {
            $table_name = "sem{$curr_semester}_subjects";
            
            // Ensure table exists
            createSemesterSubjectTable($conn, $curr_semester);
            
            // Prepare base SQL
            $list_sql = "SELECT id, subject_code, subject_name, subject_type, '$curr_semester' as semester, credits 
                         FROM $table_name";
            
            // Add type filter if specified
            $where_conditions = [];
            $param_types = '';
            $param_values = [];
            
            if ($subject_type != 'all') {
                $where_conditions[] = "subject_type = ?";
                $param_types .= 's';
                $param_values[] = $subject_type;
            }
            
            // Combine conditions
            if (!empty($where_conditions)) {
                $list_sql .= " WHERE " . implode(' AND ', $where_conditions);
            }
            
            // Prepare and execute statement
            $list_stmt = $conn->prepare($list_sql);
            
            if (!empty($param_values)) {
                $list_stmt->bind_param($param_types, ...$param_values);
            }
            
            $list_stmt->execute();
            $list_result = $list_stmt->get_result();
            
            // Fetch results
            while ($row = $list_result->fetch_assoc()) {
                $subjects[] = $row;
            }
            
            $list_stmt->close();
        }
        
        $response = array(
            'status' => 'success',
            'subjects' => $subjects
        );
        
        echo json_encode($response);
        exit;
    }
}

// Handle DELETE request for subject deletion
// Handle DELETE request for subject deletion
if ($_SERVER['REQUEST_METHOD'] == 'DELETE' || (isset($_GET['action']) && $_GET['action'] == 'delete')) {
    $subject_id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($subject_id) {
        // Check across all semester tables
        $semesters = ['III', 'IV', 'V', 'VI'];
        $deleted = false;
        
        foreach ($semesters as $semester) {
            $table_name = "sem{$semester}_subjects";
            
            $delete_sql = "DELETE FROM $table_name WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $subject_id);
            
            if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
                $deleted = true;
                break; // Exit loop once subject is deleted
            }
            
            $delete_stmt->close();
        }
        
        if ($deleted) {
            $response = array(
                'status' => 'success',
                'message' => 'Subject deleted successfully'
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Subject not found or could not be deleted'
            );
        }
        
        echo json_encode($response);
        exit;
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Subject ID is required for deletion'
        );
        
        echo json_encode($response);
        exit;
    }
}
// Check if form is submitted for adding a subject
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];
    $semester = $_POST['semester'];
    $subject_type = $_POST['subject_type'];
    $credits = $_POST['credits'];

    // Ensure semester-specific table exists
    if (!createSemesterSubjectTable($conn, substr($semester, 0, 1))) {
        $response['status'] = 'error';
        $response['message'] = "Error creating semester table.";
        echo json_encode($response);
        exit;
    }

    // Use the correct table name for the semester
    $table_name = "sem{$semester}_subjects";

    // First check if subject code already exists in any semester table
    $semesters = ['III', 'IV', 'V', 'VI'];
    $code_exists = false;
    
    foreach ($semesters as $check_semester) {
        $check_table = "sem{$check_semester}_subjects";
        $check_sql = "SELECT subject_code FROM $check_table WHERE subject_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $subject_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $code_exists = true;
            break;
        }
        $check_stmt->close();
    }

    // If subject code already exists, return error
    if ($code_exists) {
        $response['status'] = 'error';
        $response['message'] = "Subject code '$subject_code' already exists. Please use a different subject code.";
        echo json_encode($response);
        exit;
    }

    // If subject code is unique, proceed with insertion
    $sql = "INSERT INTO $table_name (subject_name, subject_code, subject_type, credits) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $subject_name, $subject_code, $subject_type, $credits);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = "Subject added successfully.";
    } else {
        $response['status'] = 'error';
        $response['message'] = "Error: " . $conn->error;
    }

    $stmt->close();
    
    // Return JSON response
    echo json_encode($response);
}

$conn->close();
?>
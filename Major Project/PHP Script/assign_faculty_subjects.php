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

// Function to safely create table
function createTableIfNotExists($conn, $tableName, $tableStructure) {
    // First, check if table exists
    $checkTableQuery = "SHOW TABLES LIKE '$tableName'";
    $result = $conn->query($checkTableQuery);
    
    // If table doesn't exist, create it
    if ($result->num_rows == 0) {
        $createTableQuery = "CREATE TABLE $tableName ($tableStructure)";
        
        if ($conn->query($createTableQuery) === TRUE) {
            return true;
        } else {
            error_log("Table creation error for $tableName: " . $conn->error);
            return false;
        }
    }
    
    return true;
}

// Ensure faculty_subject_assignments table exists
$tableStructure = "
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT(6) NOT NULL,
    subject_id INT(6) NOT NULL,
    semester ENUM('III', 'IV', 'V', 'VI') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (faculty_id, subject_id, semester)
";

if (!createTableIfNotExists($conn, "faculty_subject_assignments", $tableStructure)) {
    $response['status'] = 'error';
    $response['message'] = "Could not create faculty_subject_assignments table";
    echo json_encode($response);
    exit;
}

// Handle GET request to list assignments
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'list') {
    $faculty_filter = isset($_GET['faculty']) && $_GET['faculty'] != 'all' ? $_GET['faculty'] : null;
    $semester_filter = isset($_GET['semester']) && $_GET['semester'] != 'all' ? $_GET['semester'] : null;
    
    // Complex query to join multiple tables
    $sql = "SELECT 
                fsa.id,
                fd.faculty_name, 
                CASE fsa.semester
                    WHEN 'III' THEN s3.subject_name
                    WHEN 'IV' THEN s4.subject_name
                    WHEN 'V' THEN s5.subject_name
                    WHEN 'VI' THEN s6.subject_name
                END as subject_name,
                CASE fsa.semester
                    WHEN 'III' THEN s3.subject_code
                    WHEN 'IV' THEN s4.subject_code
                    WHEN 'V' THEN s5.subject_code
                    WHEN 'VI' THEN s6.subject_code
                END as subject_code,
                fsa.semester
            FROM faculty_subject_assignments fsa
            JOIN faculty_details fd ON fsa.faculty_id = fd.id
            LEFT JOIN semIII_subjects s3 ON (fsa.subject_id = s3.id AND fsa.semester = 'III')
            LEFT JOIN semIV_subjects s4 ON (fsa.subject_id = s4.id AND fsa.semester = 'IV')
            LEFT JOIN semV_subjects s5 ON (fsa.subject_id = s5.id AND fsa.semester = 'V')
            LEFT JOIN semVI_subjects s6 ON (fsa.subject_id = s6.id AND fsa.semester = 'VI')
            WHERE 1=1";
    
    $params = [];
    $param_types = '';
    
    if ($faculty_filter) {
        $sql .= " AND fd.id = ?";
        $params[] = $faculty_filter;
        $param_types .= 'i';
    }
    
    if ($semester_filter) {
        $sql .= " AND fsa.semester = ?";
        $params[] = $semester_filter;
        $param_types .= 's';
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    $response = [
        'status' => 'success',
        'assignments' => $assignments
    ];
    
    echo json_encode($response);
    exit;
}

// Handle POST request to assign subjects
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_id = $_POST['faculty_name'];
    $semester = $_POST['semester'];
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    
    // Validate inputs
    if (!$faculty_id || !$semester) {
        $response['status'] = 'error';
        $response['message'] = "Invalid faculty or semester.";
        echo json_encode($response);
        exit;
    }
    
    // Prepare insertion statement
    $insert_sql = "INSERT IGNORE INTO faculty_subject_assignments (faculty_id, subject_id, semester) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    
    $successful_assignments = 0;
    $failed_assignments = 0;
    
    foreach ($subjects as $subject_id) {
        $insert_stmt->bind_param("iis", $faculty_id, $subject_id, $semester);
        
        if ($insert_stmt->execute()) {
            if ($insert_stmt->affected_rows > 0) {
                $successful_assignments++;
            } else {
                $failed_assignments++;
            }
        } else {
            $failed_assignments++;
        }
    }
    
    $insert_stmt->close();
    
    // Prepare response based on assignments
    if ($successful_assignments > 0) {
        $response['status'] = $failed_assignments > 0 ? 'partial_success' : 'success';
        $response['message'] = "Assigned $successful_assignments subjects successfully. " . 
                                ($failed_assignments > 0 ? "$failed_assignments assignments skipped (possibly duplicates)." : "");
    } else {
        $response['status'] = 'error';
        $response['message'] = "No subjects could be assigned.";
    }
    
    echo json_encode($response);
    exit;
}

// Handle DELETE request for subject assignment deletion
if ($_SERVER['REQUEST_METHOD'] == 'DELETE' || (isset($_GET['action']) && $_GET['action'] == 'delete')) {
    $assignment_id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($assignment_id) {
        $delete_sql = "DELETE FROM faculty_subject_assignments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $assignment_id);
        
        if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
            $response = array(
                'status' => 'success',
                'message' => 'Subject assignment deleted successfully'
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Subject assignment not found or could not be deleted'
            );
        }
        
        $delete_stmt->close();
        echo json_encode($response);
        exit;
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Assignment ID is required for deletion'
        );
        
        echo json_encode($response);
        exit;
    }
}

$conn->close();
?>
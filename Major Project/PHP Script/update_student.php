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
    $studentFound = false;
    $studentData = null;
    $studentSemester = '';
    
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
                $studentFound = true;
                $studentData = $search_result->fetch_assoc();
                
                // Determine semester from table name
                switch ($table) {
                    case 'semester_III':
                        $studentSemester = 'III';
                        break;
                    case 'semester_IV':
                        $studentSemester = 'IV';
                        break;
                    case 'semester_V':
                        $studentSemester = 'V';
                        break;
                    case 'semester_VI':
                        $studentSemester = 'VI';
                        break;
                }
                
                break;
            }
            $search_stmt->close();
        }
    }
    
    if ($studentFound) {
        // Add semester to student data
        $studentData['semester'] = $studentSemester;
        
        $response = array(
            'status' => 'success',
            'message' => 'Student found',
            'student' => $studentData
        );
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'No student found with this roll number',
        );
    }
    
    echo json_encode($response);
    exit;
}

// Check roll number availability (for validation during updates)
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

// Process the update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_name = $_POST['student_name'];
    $branch_name = $_POST['branch_name'];
    $semester = $_POST['semester'];
    $roll_no = $_POST['roll_no'];
    $dob = $_POST['dob'];
    $original_roll_no = $_POST['original_roll_no'];
    $original_semester = $_POST['original_semester'];
    
    // Determine table names based on original and new semesters
    $original_table = 'semester_' . $original_semester;
    $new_table = 'semester_' . $semester;
    
    // Check if roll number has changed and if the new roll number already exists
    // (Skip this check if roll number hasn't changed)
    if ($roll_no !== $original_roll_no) {
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
        
        if ($rollExists) {
            $response['status'] = 'error';
            $response['message'] = "Roll number '$roll_no' already exists. Please use a different roll number.";
            echo json_encode($response);
            exit;
        }
    }
    
    // Begin transaction to ensure data integrity
    $conn->begin_transaction();
    
    try {
        // If semester has changed, we need to delete from original table and insert into new table
        if ($original_semester !== $semester) {
            // First, delete from original table
            $delete_sql = "DELETE FROM $original_table WHERE roll_no = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("s", $original_roll_no);
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Error deleting from original table: " . $conn->error);
            }
            
            // Then, insert into new table
            $insert_sql = "INSERT INTO $new_table (student_name, branch_name, roll_no, dob) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $student_name, $branch_name, $roll_no, $dob);
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Error inserting into new table: " . $conn->error);
            }
        } else {
            // Same semester, just update the record
            $update_sql = "UPDATE $original_table SET student_name = ?, branch_name = ?, roll_no = ?, dob = ? WHERE roll_no = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssss", $student_name, $branch_name, $roll_no, $dob, $original_roll_no);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Error updating student record: " . $conn->error);
            }
        }
        
        // If we got here, everything was successful
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = "Student updated successfully.";
    } catch (Exception $e) {
        // An error occurred, rollback changes
        $conn->rollback();
        
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }
    
    // Close the connection
    $conn->close();
    
    // Return JSON response
    echo json_encode($response);
}
?>
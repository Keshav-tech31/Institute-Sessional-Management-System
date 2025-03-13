<?php
// view_additional_marks_api.php
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

// Process GET requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Get additional subjects for a specific semester
    if ($action == 'get_additional_subjects' && isset($_GET['semester'])) {
        $semester = $_GET['semester'];
        
        $table_name = "sem{$semester}_other_subjects";
        
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No additional subjects found for this semester.";
            echo json_encode($response);
            exit;
        }
        
        // Prepare SQL to get additional subjects
        $sql = "SELECT id, subject_name, subject_code, subject_type FROM $table_name";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $subjects = array();
            while($row = $result->fetch_assoc()) {
                $subjects[] = $row;
            }
            
            $response['status'] = 'success';
            $response['subjects'] = $subjects;
        } else {
            $response['status'] = 'error';
            $response['message'] = "No additional subjects found for this semester.";
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Get additional marks data for students
    if ($action == 'get_marks' && isset($_GET['semester']) && isset($_GET['subject_code'])) {
        $semester = $_GET['semester'];
        $subject_code = $_GET['subject_code'];
        
        // Get the subject name and type
        $subjects_table = "sem{$semester}_other_subjects";
        $subject_sql = "SELECT subject_name, subject_type FROM $subjects_table WHERE subject_code = ?";
        $subject_stmt = $conn->prepare($subject_sql);
        $subject_stmt->bind_param("s", $subject_code);
        $subject_stmt->execute();
        $subject_result = $subject_stmt->get_result();
        
        if ($subject_result->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "Subject not found.";
            echo json_encode($response);
            exit;
        }
        
        $subject_row = $subject_result->fetch_assoc();
        $subject_name = $subject_row['subject_name'];
        $subject_type = $subject_row['subject_type'];
        $subject_stmt->close();
        
        // Get students from the semester table
        $students_table = "semester_{$semester}";
        $marks_table = "sem{$semester}_other_marks";
        
        // Check if students table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$students_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No students found for this semester.";
            echo json_encode($response);
            exit;
        }
        
        // Check if other marks table exists
        $check_marks_table = $conn->query("SHOW TABLES LIKE '$marks_table'");
        if ($check_marks_table->num_rows == 0) {
            // Create the other marks table if it doesn't exist
            $create_table = "CREATE TABLE $marks_table (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT(6) NOT NULL,
                roll_no VARCHAR(50) NOT NULL,
                student_name VARCHAR(255) NOT NULL,
                subject_code VARCHAR(30) NOT NULL,
                subject_name VARCHAR(255) NOT NULL,
                marks FLOAT DEFAULT NULL,
                first_review FLOAT DEFAULT NULL,
                second_review FLOAT DEFAULT NULL,
                viva FLOAT DEFAULT NULL,
                practical_work FLOAT DEFAULT NULL,
                attendance FLOAT DEFAULT NULL,
                total_marks FLOAT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_student_subject (student_id, subject_code)
            )";
            
            if (!$conn->query($create_table)) {
                $response['status'] = 'error';
                $response['message'] = "Error creating marks table: " . $conn->error;
                echo json_encode($response);
                exit;
            }
        }
        
        // Join the students table with the marks table
        $sql = "SELECT s.id as student_id, s.student_name, s.roll_no, 
                       m.marks, m.first_review, m.second_review, m.viva, 
                       m.practical_work, m.attendance, m.total_marks 
                FROM $students_table s 
                LEFT JOIN $marks_table m ON s.id = m.student_id AND m.subject_code = ?
                ORDER BY s.roll_no";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $subject_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $students = array();
            while($student_row = $result->fetch_assoc()) {
                $students[] = $student_row;
            }
            
            $response['status'] = 'success';
            $response['students'] = $students;
            $response['subject_name'] = $subject_name;
            $response['subject_type'] = $subject_type;
        } else {
            $response['status'] = 'error';
            $response['message'] = "No students found for this semester.";
        }
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
}

$conn->close();
?>
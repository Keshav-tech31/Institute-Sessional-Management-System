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

// Process GET requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Get student information
    if ($action == 'get_student_info' && isset($_GET['student_id'])) {
        $student_id = $_GET['student_id'];
        
        $sql = "SELECT id, student_name as name, roll_no, current_semester 
                FROM students 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response['status'] = 'success';
            $response['student'] = $result->fetch_assoc();
        } else {
            $response['status'] = 'error';
            $response['message'] = "Student not found.";
        }
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
    
    // Get semesters for a student
    if ($action == 'get_student_semesters' && isset($_GET['student_id'])) {
        $student_id = $_GET['student_id'];
        
        // First get the current semester of the student
        $sql = "SELECT current_semester FROM students WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $current_semester = $student['current_semester'];
            
            // Generate a list of all semesters up to the current one
            $semesters = array();
            for ($i = 1; $i <= $current_semester; $i++) {
                $semesters[] = array(
                    'value' => $i,
                    'label' => 'Semester ' . $i
                );
            }
            
            $response['status'] = 'success';
            $response['semesters'] = $semesters;
        } else {
            $response['status'] = 'error';
            $response['message'] = "Student not found.";
        }
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
    
    // Get theory marks
    if ($action == 'get_theory_marks' && isset($_GET['student_id']) && isset($_GET['semester'])) {
        $student_id = $_GET['student_id'];
        $semester = $_GET['semester'];
        
        $marks_table = "sem{$semester}_theory_marks";
        
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$marks_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'success';
            $response['marks'] = array();
            echo json_encode($response);
            exit;
        }
        
        $sql = "SELECT subject_code, subject_name, class_test, mid_term, attendance, total_marks, sessional
                FROM $marks_table 
                WHERE student_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $marks = array();
        while ($row = $result->fetch_assoc()) {
            $marks[] = $row;
        }
        
        $response['status'] = 'success';
        $response['marks'] = $marks;
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
    
    // Get practical marks
    if ($action == 'get_practical_marks' && isset($_GET['student_id']) && isset($_GET['semester'])) {
        $student_id = $_GET['student_id'];
        $semester = $_GET['semester'];
        
        $marks_table = "sem{$semester}_practical_marks";
        
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$marks_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'success';
            $response['marks'] = array();
            echo json_encode($response);
            exit;
        }
        
        $sql = "SELECT subject_code, subject_name, practical_viva, practical_file, attendance, total_marks, practical
                FROM $marks_table 
                WHERE student_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $marks = array();
        while ($row = $result->fetch_assoc()) {
            $marks[] = $row;
        }
        
        $response['status'] = 'success';
        $response['marks'] = $marks;
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
    
    // Get project marks (minor or major)
    if ($action == 'get_project_marks' && isset($_GET['student_id']) && isset($_GET['semester']) && isset($_GET['type'])) {
        $student_id = $_GET['student_id'];
        $semester = $_GET['semester'];
        $type = $_GET['type']; // 'minor' or 'major'
        
        $marks_table = "sem{$semester}_" . $type . "_project";
        
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$marks_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'success';
            $response['marks'] = array();
            echo json_encode($response);
            exit;
        }
        
        $sql = "SELECT subject_code, subject_name, first_review, second_review, viva, practical_work, attendance, total_marks
                FROM $marks_table 
                WHERE student_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $marks = array();
        while ($row = $result->fetch_assoc()) {
            $marks[] = $row;
        }
        
        $response['status'] = 'success';
        $response['marks'] = $marks;
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
    
    // Get other assessment types (industrial exposure, general proficiency, industrial training)
    if ($action == 'get_other_assessment' && isset($_GET['student_id']) && isset($_GET['semester']) && isset($_GET['type'])) {
        $student_id = $_GET['student_id'];
        $semester = $_GET['semester'];
        $type = $_GET['type']; // 'industrial_exposure', 'general_proficiency', 'industrial_training'
        
        $marks_table = "sem{$semester}_" . $type;
        
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$marks_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'success';
            $response['marks'] = array();
            echo json_encode($response);
            exit;
        }
        
        $sql = "SELECT subject_code, subject_name, marks
                FROM $marks_table 
                WHERE student_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $marks = array();
        while ($row = $result->fetch_assoc()) {
            $marks[] = $row;
        }
        
        $response['status'] = 'success';
        $response['marks'] = $marks;
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
}

$conn->close();
?>
<?php
//view_marks_api.php
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
    
    // Get subjects for a specific semester
    if ($action == 'get_subjects' && isset($_GET['semester'])) {
        $semester = $_GET['semester'];
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';
        
        $table_name = "sem{$semester}_subjects";
        
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No subjects found for this semester.";
            echo json_encode($response);
            exit;
        }
        
        // Prepare SQL based on type filter
        $sql = "SELECT id, subject_name, subject_code, subject_type, credits FROM $table_name";
        
        if ($type != 'all') {
            if ($type == 'THEORY') {
                $sql .= " WHERE subject_type IN ('THEORY_ONLY', 'THEORY_PRACTICAL')";
            } elseif ($type == 'PRACTICAL') {
                $sql .= " WHERE subject_type IN ('PRACTICAL_ONLY', 'THEORY_PRACTICAL')";
            }
        }
        
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
            $response['message'] = "No subjects found for this semester.";
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Get marks data for students
    if ($action == 'get_marks' && isset($_GET['semester']) && isset($_GET['subject_code'])) {
        $semester = $_GET['semester'];
        $subject_code = $_GET['subject_code'];
        $marks_type = isset($_GET['marks_type']) ? $_GET['marks_type'] : 'both';
        
        // Get the subject name
        $subjects_table = "sem{$semester}_subjects";
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
        $theory_marks_table = "sem{$semester}_theory_marks";
        $practical_marks_table = "sem{$semester}_practical_marks";
        
        // Check if students table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$students_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No students found for this semester.";
            echo json_encode($response);
            exit;
        }
        
        // Get students list
        $students_sql = "SELECT id, student_name, roll_no FROM $students_table ORDER BY roll_no";
        $students_result = $conn->query($students_sql);
        
        if ($students_result->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No students found for this semester.";
            echo json_encode($response);
            exit;
        }
        
        $students = array();
        while($student_row = $students_result->fetch_assoc()) {
            $student = array(
                'student_id' => $student_row['id'],
                'student_name' => $student_row['student_name'],
                'roll_no' => $student_row['roll_no']
            );
            
            // Get theory marks if needed
            if ($marks_type == 'theory' || $marks_type == 'both') {
                // Check if theory marks table exists
                $check_theory_table = $conn->query("SHOW TABLES LIKE '$theory_marks_table'");
                if ($check_theory_table->num_rows > 0) {
                    $theory_sql = "SELECT class_test, mid_term, attendance, total_marks, sessional 
                                  FROM $theory_marks_table 
                                  WHERE student_id = ? AND subject_code = ?";
                    $theory_stmt = $conn->prepare($theory_sql);
                    $theory_stmt->bind_param("is", $student_row['id'], $subject_code);
                    $theory_stmt->execute();
                    $theory_result = $theory_stmt->get_result();
                    
                    if ($theory_result->num_rows > 0) {
                        $student['theory'] = $theory_result->fetch_assoc();
                    } else {
                        $student['theory'] = null;
                    }
                    $theory_stmt->close();
                }
            }
            
            // Get practical marks if needed
            if ($marks_type == 'practical' || $marks_type == 'both') {
                // Check if practical marks table exists
                $check_practical_table = $conn->query("SHOW TABLES LIKE '$practical_marks_table'");
                if ($check_practical_table->num_rows > 0) {
                    $practical_sql = "SELECT practical_viva, practical_file, attendance, total_marks, practical 
                                     FROM $practical_marks_table 
                                     WHERE student_id = ? AND subject_code = ?";
                    $practical_stmt = $conn->prepare($practical_sql);
                    $practical_stmt->bind_param("is", $student_row['id'], $subject_code);
                    $practical_stmt->execute();
                    $practical_result = $practical_stmt->get_result();
                    
                    if ($practical_result->num_rows > 0) {
                        $student['practical'] = $practical_result->fetch_assoc();
                    } else {
                        $student['practical'] = null;
                    }
                    $practical_stmt->close();
                }
            }
            
            $students[] = $student;
        }
        
        $response['status'] = 'success';
        $response['students'] = $students;
        $response['subject_name'] = $subject_name;
        $response['subject_type'] = $subject_type;
        
        echo json_encode($response);
        exit;
    }
}

$conn->close();
?>
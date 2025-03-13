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

// Function to check if practical marks table exists, create if it doesn't
function ensurePracticalMarksTable($conn, $semester) {
    $table_name = "sem{$semester}_practical_marks";
    
    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows == 0) {
        $create_table = "CREATE TABLE $table_name (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT(6) NOT NULL,
            roll_no VARCHAR(50) NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            subject_code VARCHAR(30) NOT NULL,
            subject_name VARCHAR(255) NOT NULL,
            practical_viva FLOAT DEFAULT NULL,
            practical_file FLOAT DEFAULT NULL,
            attendance FLOAT DEFAULT NULL,
            total_marks FLOAT DEFAULT NULL,
            practical FLOAT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_subject (student_id, subject_code)
        )";

        if (!$conn->query($create_table)) {
            return false;
        }
    } else {
        // Check if roll_no column exists
        $check_columns = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'roll_no'");
        if ($check_columns->num_rows == 0) {
            $alter_table = "ALTER TABLE $table_name ADD COLUMN roll_no VARCHAR(50) NOT NULL AFTER student_id";
            $conn->query($alter_table);
        }
        
        // Check if student_name column exists
        $check_columns = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'student_name'");
        if ($check_columns->num_rows == 0) {
            $alter_table = "ALTER TABLE $table_name ADD COLUMN student_name VARCHAR(255) NOT NULL AFTER roll_no";
            $conn->query($alter_table);
        }
        
        // Check if subject_name column exists
        $check_columns = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'subject_name'");
        if ($check_columns->num_rows == 0) {
            $alter_table = "ALTER TABLE $table_name ADD COLUMN subject_name VARCHAR(255) NOT NULL AFTER subject_code";
            $conn->query($alter_table);
        }
        
        // Check if total_marks column exists
        $check_columns = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'total_marks'");
        if ($check_columns->num_rows == 0) {
            $alter_table = "ALTER TABLE $table_name ADD COLUMN total_marks FLOAT DEFAULT NULL AFTER attendance";
            $conn->query($alter_table);
        }
    }
    return true;
}

// Process GET requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Get students for a specific semester and subject
    if ($action == 'get_students' && isset($_GET['semester']) && isset($_GET['subject'])) {
        $semester = $_GET['semester'];
        $subject_code = $_GET['subject'];
        
        // First ensure practical marks table exists
        ensurePracticalMarksTable($conn, $semester);
        
        // Get the subject name
        $subjects_table = "sem{$semester}_subjects";
        $subject_sql = "SELECT subject_name FROM $subjects_table WHERE subject_code = ?";
        $subject_stmt = $conn->prepare($subject_sql);
        $subject_stmt->bind_param("s", $subject_code);
        $subject_stmt->execute();
        $subject_result = $subject_stmt->get_result();
        
        $subject_name = "";
        if ($subject_result->num_rows > 0) {
            $subject_row = $subject_result->fetch_assoc();
            $subject_name = $subject_row['subject_name'];
        }
        $subject_stmt->close();
        
        // Get students from the semester table
        $students_table = "semester_{$semester}";
        $marks_table = "sem{$semester}_practical_marks";
        
        // Check if students table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$students_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No students found for this semester.";
            echo json_encode($response);
            exit;
        }
        
        // Join with marks table to get existing marks data
        $sql = "SELECT s.id, s.student_name, s.roll_no, 
                       pm.practical_viva, pm.practical_file, pm.attendance, pm.total_marks, pm.practical 
                FROM $students_table s 
                LEFT JOIN $marks_table pm ON s.id = pm.student_id AND pm.subject_code = ?
                ORDER BY s.roll_no";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $subject_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $students = array();
            while($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            
            $response['status'] = 'success';
            $response['students'] = $students;
            $response['subject_name'] = $subject_name;
        } else {
            $response['status'] = 'error';
            $response['message'] = "No students found for this semester.";
        }
        
        $stmt->close();
        echo json_encode($response);
        exit;
    }
}

// Process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Save practical marks
    if ($action == 'save_practical_marks' && isset($_POST['semester']) && isset($_POST['subject_code']) && isset($_POST['students'])) {
        $semester = $_POST['semester'];
        $subject_code = $_POST['subject_code'];
        $students = $_POST['students'];
        
        // Get the subject name
        $subjects_table = "sem{$semester}_subjects";
        $subject_sql = "SELECT subject_name FROM $subjects_table WHERE subject_code = ?";
        $subject_stmt = $conn->prepare($subject_sql);
        $subject_stmt->bind_param("s", $subject_code);
        $subject_stmt->execute();
        $subject_result = $subject_stmt->get_result();
        
        $subject_name = "";
        if ($subject_result->num_rows > 0) {
            $subject_row = $subject_result->fetch_assoc();
            $subject_name = $subject_row['subject_name'];
        } else {
            $response['status'] = 'error';
            $response['message'] = "Subject not found.";
            echo json_encode($response);
            exit;
        }
        $subject_stmt->close();
        
        // Ensure practical marks table exists
        if (!ensurePracticalMarksTable($conn, $semester)) {
            $response['status'] = 'error';
            $response['message'] = "Error creating marks table.";
            echo json_encode($response);
            exit;
        }
        
        $marks_table = "sem{$semester}_practical_marks";
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $insert_sql = "INSERT INTO $marks_table 
                           (student_id, roll_no, student_name, subject_code, subject_name, practical_viva, practical_file, attendance, total_marks, practical) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE 
                           roll_no = VALUES(roll_no),
                           student_name = VALUES(student_name),
                           subject_name = VALUES(subject_name),
                           practical_viva = VALUES(practical_viva),
                           practical_file = VALUES(practical_file),
                           attendance = VALUES(attendance),
                           total_marks = VALUES(total_marks),
                           practical = VALUES(practical)";
            
            $insert_stmt = $conn->prepare($insert_sql);
            
            // Track success/failure for each student
            $success_count = 0;
            $total_students = count($students);
            
            foreach ($students as $student) {
                // Convert empty strings to null
                $practical_viva = $student['practical_viva'] !== '' ? $student['practical_viva'] : null;
                $practical_file = $student['practical_file'] !== '' ? $student['practical_file'] : null;
                $attendance = $student['attendance'] !== '' ? $student['attendance'] : null;
                $practical = $student['practical'] !== '' ? $student['practical'] : null;
                
                // Calculate total marks (practical_viva + practical_file + attendance)
                $total_marks = null;
                if (!is_null($practical_viva) || !is_null($practical_file) || !is_null($attendance)) {
                    $practical_viva_val = is_null($practical_viva) ? 0 : $practical_viva;
                    $practical_file_val = is_null($practical_file) ? 0 : $practical_file;
                    $attendance_val = is_null($attendance) ? 0 : $attendance;
                    $total_marks = $practical_viva_val + $practical_file_val + $attendance_val;
                }
                
                $insert_stmt->bind_param("issssddddd", 
                    $student['student_id'], 
                    $student['roll_no'],
                    $student['student_name'],
                    $subject_code,
                    $subject_name,
                    $practical_viva,
                    $practical_file,
                    $attendance,
                    $total_marks,
                    $practical
                );
                
                if ($insert_stmt->execute()) {
                    $success_count++;
                }
            }
            
            // Commit transaction if all went well
            $conn->commit();
            
            if ($success_count == $total_students) {
                $response['status'] = 'success';
                $response['message'] = "All marks saved successfully.";
            } else {
                $response['status'] = 'partial';
                $response['message'] = "Saved marks for $success_count out of $total_students students.";
            }
            
            $insert_stmt->close();
            
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $response['status'] = 'error';
            $response['message'] = "Error: " . $e->getMessage();
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Delete practical marks for a student
    if ($action == 'delete_practical_marks' && isset($_POST['semester']) && isset($_POST['subject_code']) && isset($_POST['student_id'])) {
        $semester = $_POST['semester'];
        $subject_code = $_POST['subject_code'];
        $student_id = $_POST['student_id'];
        
        $marks_table = "sem{$semester}_practical_marks";
        
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$marks_table'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No marks found for this semester.";
            echo json_encode($response);
            exit;
        }
        
        // Delete marks for the specific student and subject
        $delete_sql = "DELETE FROM $marks_table WHERE student_id = ? AND subject_code = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $student_id, $subject_code);
        
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                $response['status'] = 'success';
                $response['message'] = "Marks deleted successfully.";
            } else {
                $response['status'] = 'error';
                $response['message'] = "No marks found for this student.";
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = "Error deleting marks: " . $conn->error;
        }
        
        $delete_stmt->close();
        echo json_encode($response);
        exit;
    }
}

$conn->close();
?>
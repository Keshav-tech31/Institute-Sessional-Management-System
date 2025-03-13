<?php
//other_marks.php
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

// Function to ensure other marks table exists
function ensureOtherMarksTable($conn, $semester)
{
    $table_name = "sem{$semester}_other_marks";

    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows == 0) {
        $create_table = "CREATE TABLE $table_name (
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
            return false;
        }
    }
    return true;
}

// Process GET requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Get other subjects for a specific semester
    if ($action == 'get_other_subjects' && isset($_GET['semester'])) {
        $semester = $_GET['semester'];

        $table_name = "sem{$semester}_other_subjects";

        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check_table->num_rows == 0) {
            $response['status'] = 'error';
            $response['message'] = "No other subjects found for this semester.";
            echo json_encode($response);
            exit;
        }

        // Query to get other subjects
        $sql = "SELECT id, subject_name, subject_code, subject_type FROM $table_name";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $subjects = array();
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row;
            }

            $response['status'] = 'success';
            $response['subjects'] = $subjects;
        } else {
            $response['status'] = 'error';
            $response['message'] = "No other subjects found for this semester.";
        }

        echo json_encode($response);
        exit;
    }

    // Get students for a specific semester and subject
    if ($action == 'get_students' && isset($_GET['semester']) && isset($_GET['subject'])) {
        $semester = $_GET['semester'];
        $subject_code = $_GET['subject'];

        // First ensure other marks table exists
        ensureOtherMarksTable($conn, $semester);

        // Get the subject name and type
        $subjects_table = "sem{$semester}_other_subjects";
        $subject_sql = "SELECT subject_name, subject_type FROM $subjects_table WHERE subject_code = ?";
        $subject_stmt = $conn->prepare($subject_sql);
        $subject_stmt->bind_param("s", $subject_code);
        $subject_stmt->execute();
        $subject_result = $subject_stmt->get_result();

        $subject_name = "";
        $subject_type = "";
        if ($subject_result->num_rows > 0) {
            $subject_row = $subject_result->fetch_assoc();
            $subject_name = $subject_row['subject_name'];
            $subject_type = $subject_row['subject_type'];
        }
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

        // Join with marks table to get existing marks data
        $sql = "SELECT s.id, s.student_name, s.roll_no, 
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
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
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

// Process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Save other marks
    if ($action == 'save_other_marks' && isset($_POST['semester']) && isset($_POST['subject_code']) && isset($_POST['students'])) {
        $semester = $_POST['semester'];
        $subject_code = $_POST['subject_code'];
        $subject_name = isset($_POST['subject_name']) ? $_POST['subject_name'] : '';
        $students = $_POST['students'];

        // Ensure other marks table exists
        if (!ensureOtherMarksTable($conn, $semester)) {
            $response['status'] = 'error';
            $response['message'] = "Error creating marks table.";
            echo json_encode($response);
            exit;
        }

        // Get the subject name if not provided
        if (empty($subject_name)) {
            $subjects_table = "sem{$semester}_other_subjects";
            $subject_sql = "SELECT subject_name FROM $subjects_table WHERE subject_code = ?";
            $subject_stmt = $conn->prepare($subject_sql);
            $subject_stmt->bind_param("s", $subject_code);
            $subject_stmt->execute();
            $subject_result = $subject_stmt->get_result();

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
        }

        $marks_table = "sem{$semester}_other_marks";

        // Start transaction
        $conn->begin_transaction();

        try {
            // For project work, we need to handle multiple fields
            // For project work, we need to handle multiple fields
            if (stripos($subject_name, 'project work') !== false) {
                $insert_sql = "INSERT INTO $marks_table 
                   (student_id, roll_no, student_name, subject_code, subject_name, 
                    first_review, second_review, viva, practical_work, attendance, total_marks) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE 
                   roll_no = VALUES(roll_no),
                   student_name = VALUES(student_name),
                   subject_name = VALUES(subject_name),
                   first_review = VALUES(first_review),
                   second_review = VALUES(second_review),
                   viva = VALUES(viva),
                   practical_work = VALUES(practical_work),
                   attendance = VALUES(attendance),
                   total_marks = VALUES(total_marks)";

                $insert_stmt = $conn->prepare($insert_sql);

                // Track success/failure for each student
                $success_count = 0;
                $total_students = count($students);

                foreach ($students as $student) {
                    // Convert empty strings to null
                    $first_review = $student['first_review'] !== '' ? $student['first_review'] : null;
                    $second_review = $student['second_review'] !== '' ? $student['second_review'] : null;
                    $viva = $student['viva'] !== '' ? $student['viva'] : null;
                    $practical_work = $student['practical_work'] !== '' ? $student['practical_work'] : null;
                    $attendance = $student['attendance'] !== '' ? $student['attendance'] : null;

                    // Calculate total marks
                    $total_marks = null;
                    if (
                        !is_null($first_review) || !is_null($second_review) || !is_null($viva) ||
                        !is_null($practical_work) || !is_null($attendance)
                    ) {
                        $first_review_val = is_null($first_review) ? 0 : $first_review;
                        $second_review_val = is_null($second_review) ? 0 : $second_review;
                        $viva_val = is_null($viva) ? 0 : $viva;
                        $practical_work_val = is_null($practical_work) ? 0 : $practical_work;
                        $attendance_val = is_null($attendance) ? 0 : $attendance;

                        // Calculate total marks
                        $total_marks = $first_review_val + $second_review_val + $viva_val +
                            $practical_work_val + $attendance_val;

                        // Validate the maximum marks based on project type
                        if (stripos($subject_name, 'major') !== false) {
                            // For Major Project Work, practical work max is 15, total max is 55
                            if ($practical_work_val > 15) {
                                $practical_work = 15;
                                $total_marks = $first_review_val + $second_review_val + $viva_val + 15 + $attendance_val;
                            }
                        } else {
                            // For Minor Project Work, practical work max is 10, total max is 50
                            if ($practical_work_val > 10) {
                                $practical_work = 10;
                                $total_marks = $first_review_val + $second_review_val + $viva_val + 10 + $attendance_val;
                            }
                        }
                    }

                    $insert_stmt->bind_param(
                        "issssdddddd",
                        $student['student_id'],
                        $student['roll_no'],
                        $student['student_name'],
                        $subject_code,
                        $subject_name,
                        $first_review,
                        $second_review,
                        $viva,
                        $practical_work,
                        $attendance,
                        $total_marks
                    );

                    if ($insert_stmt->execute()) {
                        $success_count++;
                    }
                }
            } else {
                // For other subjects, we just handle a single marks field
                $insert_sql = "INSERT INTO $marks_table 
                               (student_id, roll_no, student_name, subject_code, subject_name, marks) 
                               VALUES (?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE 
                               roll_no = VALUES(roll_no),
                               student_name = VALUES(student_name),
                               subject_name = VALUES(subject_name),
                               marks = VALUES(marks)";

                $insert_stmt = $conn->prepare($insert_sql);

                // Track success/failure for each student
                $success_count = 0;
                $total_students = count($students);

                foreach ($students as $student) {
                    // Convert empty strings to null
                    $marks = $student['marks'] !== '' ? $student['marks'] : null;

                    $insert_stmt->bind_param(
                        "issssd",
                        $student['student_id'],
                        $student['roll_no'],
                        $student['student_name'],
                        $subject_code,
                        $subject_name,
                        $marks
                    );

                    if ($insert_stmt->execute()) {
                        $success_count++;
                    }
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

    // Delete other marks for a student
    if ($action == 'delete_other_marks' && isset($_POST['semester']) && isset($_POST['subject_code']) && isset($_POST['student_id'])) {
        $semester = $_POST['semester'];
        $subject_code = $_POST['subject_code'];
        $student_id = $_POST['student_id'];

        $marks_table = "sem{$semester}_other_marks";

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

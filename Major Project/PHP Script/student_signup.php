<?php
// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
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

// Handle AJAX request to check roll number
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['check_roll'])) {
    $roll_no = $_GET['check_roll'];
    
    // Validate roll number format
    if (!preg_match('/^\d{11}$/', $roll_no)) {
        $response = array(
            'status' => 'error',
            'message' => 'Invalid roll number format',
            'valid' => false
        );
        echo json_encode($response);
        exit;
    }
    
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
                $check_stmt->close();
                break;
            }
            $check_stmt->close();
        }
    }
    
    // We want student registration to succeed if roll_no exists in the semester tables
    // (opposite of the availability check in add_student.php)
    $response = array(
        'status' => $rollExists ? 'success' : 'error',
        'message' => $rollExists ? 'Roll number verified' : 'Roll number not found in our records',
        'valid' => $rollExists
    );
    
    echo json_encode($response);
    exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $rollno = isset($_POST['rollno']) ? $_POST['rollno'] : '';

    // Validate required fields
    if (empty($name) || empty($username) || empty($email) || empty($phone) || empty($rollno)) {
        $response['status'] = 'error';
        $response['message'] = "All fields are required.";
        echo json_encode($response);
        exit;
    }

    // Validate roll number format
    if (!preg_match('/^\d{11}$/', $rollno)) {
        $response['status'] = 'error';
        $response['message'] = "Invalid roll number format.";
        echo json_encode($response);
        exit;
    }

    // First check if roll number exists in any semester table (must exist to allow registration)
    $tables = array('semester_III', 'semester_IV', 'semester_V', 'semester_VI');
    $rollExists = false;
    
    foreach ($tables as $table) {
        // Make sure table exists before querying
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            $check_sql = "SELECT roll_no FROM $table WHERE roll_no = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $rollno);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $rollExists = true;
                $check_stmt->close();
                break;
            }
            $check_stmt->close();
        }
    }
    
    // If roll number doesn't exist in semester tables, return error
    if (!$rollExists) {
        $response['status'] = 'error';
        $response['message'] = "Roll number not found in our records. Please contact your institute.";
        echo json_encode($response);
        exit;
    }

    // Check if users table exists, create if not
    $check_users_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_users_table->num_rows == 0) {
        // Create users table
        $create_table_sql = "CREATE TABLE users (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            roll_number VARCHAR(20) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table_sql)) {
            $response['status'] = 'error';
            $response['message'] = "Error creating users table: " . $conn->error;
            echo json_encode($response);
            exit;
        }
    }

    // Check if username already exists
    $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $username_result = $check_username->get_result();
    
    if ($username_result->num_rows > 0) {
        $check_username->close();
        $response['status'] = 'error';
        $response['message'] = "Username already exists!";
        echo json_encode($response);
        exit;
    }
    $check_username->close();

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $email_result = $check_email->get_result();
    
    if ($email_result->num_rows > 0) {
        $check_email->close();
        $response['status'] = 'error';
        $response['message'] = "Email already registered!";
        echo json_encode($response);
        exit;
    }
    $check_email->close();

    // Check if roll number already registered in users table
    $check_rollno = $conn->prepare("SELECT id FROM users WHERE roll_number = ?");
    $check_rollno->bind_param("s", $rollno);
    $check_rollno->execute();
    $rollno_result = $check_rollno->get_result();
    
    if ($rollno_result->num_rows > 0) {
        $check_rollno->close();
        $response['status'] = 'error';
        $response['message'] = "This roll number is already registered!";
        echo json_encode($response);
        exit;
    }
    $check_rollno->close();

    // Insert data into users table
    $insert_stmt = $conn->prepare("INSERT INTO users (name, username, email, phone, roll_number) 
            VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("sssss", $name, $username, $email, $phone, $rollno);

    if ($insert_stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = "Registration successful";
    } else {
        $response['status'] = 'error';
        $response['message'] = "Registration failed: " . $conn->error;
    }

    $insert_stmt->close();
    $conn->close();
    
    // Return JSON response
    echo json_encode($response);
    exit;
}

// If no valid request, return error
$response['status'] = 'error';
$response['message'] = "Invalid request";
echo json_encode($response);
?>
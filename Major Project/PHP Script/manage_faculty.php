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

// Function to create faculty table if it doesn't exist
function createFacultyTable($conn) {
    $table_name = "faculty_details";
    
    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows == 0) {
        $create_table = "CREATE TABLE $table_name (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            faculty_name VARCHAR(255) NOT NULL,
            phone_number VARCHAR(10) NOT NULL UNIQUE,
            designation ENUM('PROFESSOR', 'ASSOCIATE_PROFESSOR', 'ASSISTANT_PROFESSOR', 'LECTURER', 'HEAD_OF_DEPARTMENT') NOT NULL,
            department ENUM('COMPUTER_SCIENCE') NOT NULL,
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
    // Check phone number availability
    if (isset($_GET['check_phone'])) {
        $phone_number = $_GET['check_phone'];
        
        // Ensure faculty table exists
        createFacultyTable($conn);
        
        $check_sql = "SELECT phone_number FROM faculty_details WHERE phone_number = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $phone_number);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $response = array(
            'status' => $check_result->num_rows == 0 ? 'success' : 'error',
            'message' => $check_result->num_rows == 0 ? 'Phone number is available' : 'Phone number already exists',
            'available' => $check_result->num_rows == 0
        );
        
        echo json_encode($response);
        $check_stmt->close();
        exit;
    }
    
    // List faculties
    if (isset($_GET['action']) && $_GET['action'] == 'list') {
        $department = isset($_GET['department']) ? $_GET['department'] : 'all';
        $designation = isset($_GET['designation']) ? $_GET['designation'] : 'all';
        
        // Ensure faculty table exists
        createFacultyTable($conn);
        
        // Prepare base SQL
        $list_sql = "SELECT id, faculty_name, phone_number, designation, department 
                     FROM faculty_details";
        
        // Add filter conditions
        $where_conditions = [];
        $param_types = '';
        $param_values = [];
        
        if ($department != 'all') {
            $where_conditions[] = "department = ?";
            $param_types .= 's';
            $param_values[] = $department;
        }
        
        if ($designation != 'all') {
            $where_conditions[] = "designation = ?";
            $param_types .= 's';
            $param_values[] = $designation;
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
        $faculties = [];
        while ($row = $list_result->fetch_assoc()) {
            $faculties[] = $row;
        }
        
        $response = array(
            'status' => 'success',
            'faculties' => $faculties
        );
        
        echo json_encode($response);
        $list_stmt->close();
        exit;
    }
}

// Handle DELETE request for faculty deletion
if ($_SERVER['REQUEST_METHOD'] == 'DELETE' || (isset($_GET['action']) && $_GET['action'] == 'delete')) {
    $faculty_id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($faculty_id) {
        // Ensure faculty table exists
        createFacultyTable($conn);
        
        $delete_sql = "DELETE FROM faculty_details WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $faculty_id);
        
        if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
            $response = array(
                'status' => 'success',
                'message' => 'Faculty deleted successfully'
            );
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Faculty not found or could not be deleted'
            );
        }
        
        $delete_stmt->close();
        echo json_encode($response);
        exit;
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Faculty ID is required for deletion'
        );
        
        echo json_encode($response);
        exit;
    }
}

// Check if form is submitted for adding a faculty
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_name = $_POST['faculty_name'];
    $phone_number = $_POST['phone_number'];
    $designation = $_POST['designation'];
    $department = $_POST['department'];

    // Ensure faculty table exists
    if (!createFacultyTable($conn)) {
        $response['status'] = 'error';
        $response['message'] = "Error creating faculty table.";
        echo json_encode($response);
        exit;
    }

    // Check if phone number already exists
    $check_sql = "SELECT phone_number FROM faculty_details WHERE phone_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $phone_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // If phone number already exists, return error
    if ($check_result->num_rows > 0) {
        $response['status'] = 'error';
        $response['message'] = "Phone number '$phone_number' is already registered.";
        echo json_encode($response);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();

    // Proceed with insertion
    $sql = "INSERT INTO faculty_details (faculty_name, phone_number, designation, department) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $faculty_name, $phone_number, $designation, $department);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = "Faculty added successfully.";
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
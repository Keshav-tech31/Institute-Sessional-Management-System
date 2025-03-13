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

// Get the list of semesters
$sql = "SHOW TABLES LIKE 'semester_%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $semesters = array();
    
    while($row = $result->fetch_array()) {
        $semester_table = $row[0];
        $semester_id = str_replace('semester_', '', $semester_table);
        
        // Create a nice display name
        $semester_name = 'Semester ' . $semester_id;
        
        // Add to semesters array
        $semesters[] = array(
            'id' => $semester_id,
            'name' => $semester_name,
            'table' => $semester_table
        );
    }
    
    // Sort by semester number
    usort($semesters, function($a, $b) {
        return intval($a['id']) - intval($b['id']);
    });
    
    $response['status'] = 'success';
    $response['semesters'] = $semesters;
} else {
    $response['status'] = 'error';
    $response['message'] = 'No semesters found';
}

$conn->close();
echo json_encode($response);
?>
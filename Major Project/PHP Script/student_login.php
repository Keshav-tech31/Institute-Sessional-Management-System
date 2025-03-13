<?php
session_start();

// Add these cache control headers at the top of the file
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

// Database connection
$servername = "localhost";
$username = "root";
$password = "9528503294";
$dbname = "student_db";

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle login request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $rollno = mysqli_real_escape_string($conn, $_POST['rollno']);

    // Validate roll number format
    if (!preg_match('/^\d{11}$/', $rollno)) {
        echo "Invalid roll number format. Please enter 11 digits.";
        exit;
    }

    // Check if user exists with given username and roll number
    $sql = "SELECT * FROM users WHERE username = '$username' AND roll_number = '$rollno'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        // Create session variables
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['rollno'] = $row['roll_number'];

        // Add a session token for validation
        $_SESSION['login_token'] = md5(uniqid(rand(), true));
        $_SESSION['last_activity'] = time();

        echo "Login successful";
    } else {
        // Check if username exists
        $check_user = "SELECT * FROM users WHERE username = '$username'";
        $user_result = mysqli_query($conn, $check_user);

        if (mysqli_num_rows($user_result) > 0) {
            echo "Invalid roll number for this username";
        } else {
            echo "Username not found";
        }
    }
}

// Handle forgot username request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'forgotUsername') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $rollno = mysqli_real_escape_string($conn, $_POST['rollno']);

    // Validate roll number format
    if (!preg_match('/^\d{11}$/', $rollno)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid roll number format. Please enter 11 digits.'
        ]);
        exit;
    }

    $sql = "SELECT id, username FROM users WHERE email = '$email' AND roll_number = '$rollno'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        // Return username
        echo json_encode([
            'status' => 'success',
            'username' => $row['username'],
            'user_id' => $row['id']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No account found with the provided email and roll number'
        ]);
    }
}

// Handle change username request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'changeUsername') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $new_username = mysqli_real_escape_string($conn, $_POST['new_username']);

    // Validate new username (minimum length, alphanumeric)
    if (strlen($new_username) < 4) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username must be at least 4 characters long'
        ]);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username can only contain letters, numbers, and underscores'
        ]);
        exit;
    }

    // Check if new username already exists
    $check_query = "SELECT id FROM users WHERE username = '$new_username' AND id != '$user_id'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This username is already taken. Please choose another one.'
        ]);
        exit;
    }

    // Update username in database
    $update_query = "UPDATE users SET username = '$new_username' WHERE id = '$user_id'";

    if (mysqli_query($conn, $update_query)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Username updated successfully',
            'new_username' => $new_username
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update username: ' . mysqli_error($conn)
        ]);
    }
}

mysqli_close($conn);

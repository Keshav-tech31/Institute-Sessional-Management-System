<?php
// Start session
session_start();

// Add these cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['rollno'])) {
    // Redirect to login page if not logged in
    header("Location: PHP Script/student_login.php");
    exit;
}

// Additional session validation
if (!isset($_SESSION['last_activity'])) {
    // No activity timestamp, session might be compromised
    header("Location: PHP Script/student_logout.php");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();


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

// Get student data from database
$rollno = $_SESSION['rollno'];
$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];
$username = $_SESSION['username'];

// Determine which semester table contains this student's data
$semester = "";
$branch = "";
$dob = "";
$tables = array('semester_III', 'semester_IV', 'semester_V', 'semester_VI');

foreach ($tables as $table) {
    // Check if table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check_table) > 0) {
        // Check if student exists in this table
        $sql = "SELECT * FROM $table WHERE roll_no = '$rollno'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $student_data = mysqli_fetch_assoc($result);
            $semester = str_replace("semester_", "", $table);
            $branch = $student_data['branch_name'];
            $dob = isset($student_data['dob']) ? $student_data['dob'] : "Not available";
            break;
        }
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script type="text/javascript">
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
            window.location.href = 'student_login.php';
        }

        // Disable browser cache for this page
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #f1c40f;
            --text-color: #333;
            --light-bg: #f5f8fa;
            --white: #fff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .logo i {
            color: var(--blue);
        }

        .logo h2 {
            font-size: 1.2rem;
        }


        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 20px 0;
            box-shadow: var(--shadow);
            position: fixed;
            height: 100%;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-menu {
            padding: 20px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-item:hover,
        .menu-item.active {
            background-color: var(--secondary-color);
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .menu-item a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            flex-grow: 1;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: var(--white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .greeting h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .greeting p {
            color: #666;
        }

        .student-info {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .info-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .info-item p {
            font-size: 1.1rem;
            font-weight: 500;
            padding: 10px;
            background-color: var(--light-bg);
            border-radius: 5px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">

                    <i class="fas fa-graduation-cap fa-2x"></i>
                    <h2>Student Portal</h2>

                </div>

            </div>
            <div class="sidebar-menu">
                <div class="menu-item active">
                    <i class="fas fa-home"></i>
                    <a href="student_dashboard.php">Dashboard</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <a href="student_view_marks.html">View Marks</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="greeting">
                    <h1>Welcome, <?php echo $student_name; ?></h1>
                    <p>Here's your academic overview</p>
                </div>
                <div class="date">
                    <p><?php echo date("l, F j, Y"); ?></p>
                </div>
            </div>

            <div class="student-info">
                <div class="info-header">
                    <h2>Student Information</h2>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <p><?php echo $student_name; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Username</label>
                        <p><?php echo $username; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Branch</label>
                        <p><?php echo $branch ? $branch : "Not available"; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Current Semester</label>
                        <p><?php echo $semester ? $semester : "Not available"; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Roll Number</label>
                        <p><?php echo $rollno; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Date of Birth</label>
                        <p><?php echo $dob ? date('d-m-Y', strtotime($dob)) : "Not available"; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
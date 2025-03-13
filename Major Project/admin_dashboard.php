<?php
// Start the session at the very beginning
include './PHP Script/session_check.php';

// Configuration
$servername = "localhost";
$username = "root";
$password = "9528503294";
$dbname = "student_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get total students count
function getTotalStudents($conn)
{
    $total = 0;
    $semesters = ['III', 'IV', 'V', 'VI'];

    foreach ($semesters as $semester) {
        $table = "semester_" . $semester;

        // Check if table exists first
        $tableCheck = $conn->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM $table");
            if ($result && $row = $result->fetch_assoc()) {
                $total += $row['count'];
            }
        }
    }

    return $total;
}

// Function to get total faculty count
function getTotalFaculty($conn)
{
    // Check if faculty_details table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'faculty_details'");
    if ($tableCheck->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM faculty_details");
        if ($result && $row = $result->fetch_assoc()) {
            return $row['count'];
        }
    }
    return 0;
}

// Modified function to get all faculty members with their subjects grouped by semester
function getFacultyAssignments($conn)
{
    $facultyList = [];

    // Check if necessary tables exist
    $tableCheck = $conn->query("SHOW TABLES LIKE 'faculty_subject_assignments'");
    if ($tableCheck->num_rows == 0) {
        return $facultyList;
    }

    // First, get all faculty members
    $facultySql = "SELECT id, faculty_name, department FROM faculty_details ORDER BY faculty_name";
    $facultyResult = $conn->query($facultySql);

    if (!$facultyResult) {
        return $facultyList;
    }

    while ($faculty = $facultyResult->fetch_assoc()) {
        $facultyId = $faculty['id'];
        $facultyData = [
            'faculty_name' => $faculty['faculty_name'],
            'department' => $faculty['department'],
            'sem_III' => [],
            'sem_IV' => [],
            'sem_V' => [],
            'sem_VI' => []
        ];

        // Get subjects for Semester III
        $sql = "SELECT s3.subject_name 
                FROM faculty_subject_assignments fsa
                JOIN semIII_subjects s3 ON fsa.subject_id = s3.id
                WHERE fsa.faculty_id = $facultyId AND fsa.semester = 'III'";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $facultyData['sem_III'][] = $row['subject_name'];
            }
        }

        // Get subjects for Semester IV
        $sql = "SELECT s4.subject_name 
                FROM faculty_subject_assignments fsa
                JOIN semIV_subjects s4 ON fsa.subject_id = s4.id
                WHERE fsa.faculty_id = $facultyId AND fsa.semester = 'IV'";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $facultyData['sem_IV'][] = $row['subject_name'];
            }
        }

        // Get subjects for Semester V
        $sql = "SELECT s5.subject_name 
                FROM faculty_subject_assignments fsa
                JOIN semV_subjects s5 ON fsa.subject_id = s5.id
                WHERE fsa.faculty_id = $facultyId AND fsa.semester = 'V'";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $facultyData['sem_V'][] = $row['subject_name'];
            }
        }

        // Get subjects for Semester VI
        $sql = "SELECT s6.subject_name 
                FROM faculty_subject_assignments fsa
                JOIN semVI_subjects s6 ON fsa.subject_id = s6.id
                WHERE fsa.faculty_id = $facultyId AND fsa.semester = 'VI'";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $facultyData['sem_VI'][] = $row['subject_name'];
            }
        }

        $facultyList[] = $facultyData;
    }

    return $facultyList;
}

// Logout function
if (isset($_GET['logout'])) {
    // Start the session if it's not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Destroy all session data
    session_unset();
    session_destroy();

    // Clear any session cookies
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Redirect to login page with correct path
    header("Location: admin_login.php");
    exit;
}

// Get all counts and data
$totalStudents = getTotalStudents($conn);
$totalFaculty = getTotalFaculty($conn);
$facultyAssignments = getFacultyAssignments($conn);

// Close the connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Result Management System</title>
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- CSS Files -->
    <link rel="stylesheet" href="CSS/admin_dashboard.css">
    <style>
        
        .logout-container {
            margin-top: auto;
            padding: 20px 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 20px;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            background-color: transparent;
            border: none;
            color: #f44336;
            cursor: pointer;
            font-size: 14px;
            padding: 10px;
            width: 100%;
            border-radius: 4px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: rgba(244, 67, 54, 0.2);
        }

        .logout-btn i {
            margin-right: 10px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .nav-menu {
            flex-grow: 1;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <!-- Main Container -->
    <div class="container">
        <!-- Left Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-graduation-cap fa-2x"></i>
                <h2><big>Sessional Management</big></h2>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li class="active">
                        <a href="admin_dashboard.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_student.html">
                            <i class="fas fa-user-graduate"></i>
                            <span>Manage Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_faculty.html">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Manage Faculty</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_subject.html">
                            <i class="fas fa-book"></i>
                            <span>Manage Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a href="assign_subject.html">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Assign Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a href="upload_marks.html">
                            <i class="fas fa-pen"></i>
                            <span>Upload Marks</span>
                        </a>
                    </li>
                    <li>
                        <a href="view_result.html">
                            <i class="fas fa-poll"></i>
                            <span>View Marks</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="logout-container">
                <a href="?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Welcome Message Banner -->
            <div class="welcome-banner">
                <div class="google-dots">
                    <span class="dot blue"></span>
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <div class="welcome-text">
                    <h1>
                        <span class="word blue-text">WELCOME</span>
                        <span class="word red-text">TO</span>
                        <span class="word yellow-text">THE</span>
                        <span class="word green-text">INSTITUTE</span>
                        <span class="word blue-text">SESSIONAL</span>
                        <span class="word red-text">MANAGEMENT</span>
                        <span class="word yellow-text">SYSTEM</span>
                    </h1>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Students</h3>
                        <p class="number"><?php echo number_format($totalStudents); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Faculties</h3>
                        <p class="number"><?php echo number_format($totalFaculty); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Branch</h3>
                        <p class="number"><small>Computer Science & Engineering</small></p>
                    </div>
                    <div class="stat-card">
                        <h3>Current Session</h3>
                        <p class="number">2024-25</p>
                    </div>
                </div>

                <!-- Faculty Subject Assignment Section -->
                <div class="section-title">
                    <h2>Faculty Subject Assignments</h2>
                </div>
                <div class="faculty-subjects">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Faculty Name</th>
                                <th>Department</th>
                                <th>Semester IV</th>
                                <th>Semester VI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($facultyAssignments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No faculty-subject assignments found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($facultyAssignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['faculty_name']); ?></td>
                                        <td><?php
                                            // Format department name for display
                                            $dept = htmlspecialchars($assignment['department']);
                                            echo str_replace('_', ' ', $dept);
                                            ?></td>

                                        <td><?php echo !empty($assignment['sem_IV']) ? htmlspecialchars(implode(', ', $assignment['sem_IV'])) : '-'; ?></td>
                                        <td><?php echo !empty($assignment['sem_VI']) ? htmlspecialchars(implode(', ', $assignment['sem_VI'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript Files -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate in the banner elements
            setTimeout(function() {
                document.querySelector('.welcome-text').classList.add('show');

                // Animate dots sequentially
                const dots = document.querySelectorAll('.dot');
                dots.forEach((dot, index) => {
                    setTimeout(() => {
                        dot.classList.add('show');
                    }, index * 150);
                });
            }, 300);
        });
    </script>

    <script>
        // Toggle sidebar on mobile
        const menuToggle = document.getElementById('menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
        }
    </script>
</body>

</html>
<?php
// Start session
session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

// Process login form submission
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Hardcoded credentials (same as in your JS)
    $admin_username = "admin";
    $admin_password = "123";
    
    if ($username === $admin_username && $password === $admin_password) {
        // Set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_email'] = 'admin@example.com'; // You can change this or make it dynamic
        
        // Set a flag for successful login (will be used for SweetAlert)
        $login_success = true;
    } else {
        $error_message = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <!-- Improved Poppins font import with various weights -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/admin_login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <main>
        <h1>Admin Login</h1>

        <div class="login">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <?php if (!empty($error_message)): ?>
                    <div class="error-message" id="error-message"><?php echo $error_message; ?></div>
                    <script>
                        // Show SweetAlert for PHP-generated error
                        Swal.fire({
                            title: "Login Failed!",
                            text: "<?php echo $error_message; ?>",
                            icon: "error",
                            confirmButtonText: "Try Again",
                            confirmButtonColor: "#4285f4"
                        });
                    </script>
                <?php endif; ?>
                
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter Your Username Please" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter Your Password Please" required>
                </div>

                <div class="btn">
                    <button type="button" onclick="window.location.href='index.html'">Back</button>
                    <button type="submit">Login</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Client-side validation as fallback
        document.querySelector('form').addEventListener('submit', function(event) {
            const username = document.getElementById("username").value;
            const password = document.getElementById("password").value;
            
            // Only use this if form is directly submitted (not through the POST method)
            if (username !== "admin" || password !== "123") {
                event.preventDefault();
                Swal.fire({
                    title: "Login Failed!",
                    text: "Invalid Username or Password",
                    icon: "error",
                    confirmButtonText: "Try Again",
                    confirmButtonColor: "#4285f4"
                });
            }
        });
        
        <?php if (isset($login_success) && $login_success): ?>
            // Show success SweetAlert and then redirect
            Swal.fire({
                title: "Login Successful!",
                text: "Welcome back, Admin!",
                icon: "success",
                confirmButtonText: "Continue to Dashboard",
                confirmButtonColor: "#4285f4"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "admin_dashboard.php";
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
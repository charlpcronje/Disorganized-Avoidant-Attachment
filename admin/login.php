<?php
// /admin/login.php
// Admin login page

// Start session
session_start();

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Initialize logger
$logger = new Logger();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Initialize error message
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Prepare SQL statement
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Check if user exists
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Log successful login
                    $logger->info("User {$user['username']} logged in successfully");
                    
                    // Redirect to dashboard
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Invalid password.';
                    $logger->warning("Failed login attempt for user $username (invalid password)");
                }
            } else {
                $error = 'Invalid username.';
                $logger->warning("Failed login attempt for unknown user $username");
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            $logger->error("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo SITE_NAME; ?></h1>
            <h2>Admin Login</h2>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form action="login.php" method="post" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-login">Login</button>
            </div>
        </form>
        
        <div class="login-footer">
            <a href="../index.php">Return to Site</a>
        </div>
    </div>
</body>
</html>
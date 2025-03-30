<?php
session_start();
$host = 'localhost'; // Change if needed
$user = 'root'; // Your database username
$pass = 'mary'; // Your database password
$dbname = 'voting_system1'; // Your database name
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            header("Location: dashboard.php"); 
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VoteSecure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
        }
        
        .login-container {
            width: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: white;
        }
        
        .login-form {
            width: 80%;
            max-width: 400px;
            padding: 20px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo {
            background-color: #121826;
            color: white;
            width: 40px;
            height: 40px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
        }
        
        h1 {
            color: #121826;
            font-size: 24px;
            margin-bottom: 5px;
            text-align: center;
        }
        
        .subtitle {
            color: #505050;
            font-size: 14px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .tab-container {
            display: flex;
            margin-bottom: 20px;
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            background-color: #f7f7f7;
            cursor: pointer;
        }
        
        .tab.active {
            background-color: white;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .password-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .forgot-password {
            color: #0066cc;
            font-size: 14px;
            text-decoration: none;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input {
            margin-right: 10px;
        }
        
        .signin-btn {
            width: 100%;
            padding: 12px;
            background-color: #121826;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #888;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .divider::before {
            margin-right: 10px;
        }
        
        .divider::after {
            margin-left: 10px;
        }
        
        .social-login {
            display: flex;
            justify-content: space-between;
        }
        
        .social-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
            cursor: pointer;
            margin: 0 5px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .social-btn img {
            height: 20px;
            margin-right: 5px;
        }
        
        .features-container {
            width: 50%;
            background-color: #121826;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .features {
            max-width: 500px;
        }
        
        .features h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .features p {
            margin-bottom: 30px;
            color: #ccc;
        }
        
        .feature {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .feature-icon {
            margin-right: 15px;
            background-color: rgba(255, 255, 255, 0.1);
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 5px;
        }
        
        .feature-text h3 {
            margin: 0 0 5px 0;
        }
        
        .feature-text p {
            margin: 0;
            font-size: 14px;
        }
        
        /* Make it responsive */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .login-container, .features-container {
                width: 100%;
                height: auto;
            }
            
            .features-container {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
            <h1>Welcome to VoteSecure</h1>
            <p class="subtitle">Sign in to access your secure voting platform</p>
            
            <div class="tab-container">
                <div class="tab active">Login</div>
                <div class="tab" onclick="window.location.href='register.php'">Register</div>
            </div>
            
            <?php if (isset($error)) echo "<p style='color:red;text-align:center;'>$error</p>"; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <input type="text" id="username" name="username" placeholder="yourusername" required>
                        <i class="input-icon far fa-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="password-row">
                        <label for="password">Password</label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    <div class="input-with-icon">
                        <input type="password" id="password" name="password" placeholder="•••••••" required>
                        <i class="input-icon fas fa-lock"></i>
                    </div>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember" style="display: inline; font-weight: normal;">Remember me</label>
                </div>
                <a class="forgot-password" href="register.php">Register here!</a>
                <button type="submit" class="signin-btn">Sign in</button>
            </form>
            
            <div class="divider">Or continue with</div>
            
            <div class="social-login">
                <button class="social-btn">
                    <img src="https://www.google.com/favicon.ico" alt="Google"> Google
                </button>
                <button class="social-btn">
                    <img src="https://www.microsoft.com/favicon.ico" alt="Microsoft"> Microsoft
                </button>
            </div>
        </div>
    </div>
    
    <div class="features-container">
        <div class="features">
            <h2>Secure Electronic Voting</h2>
            <p>A reliable platform for conducting elections with transparency, security, and ease.</p>
            
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="feature-text">
                    <h3>End-to-End Encryption</h3>
                    <p>Your vote is secure and anonymous.</p>
                </div>
            </div>
            
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="feature-text">
                    <h3>Easy to Use</h3>
                    <p>Vote from anywhere, on any device.</p>
                </div>
            </div>
            
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="feature-text">
                    <h3>Real-time Results</h3>
                    <p>Instantly view election outcomes.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
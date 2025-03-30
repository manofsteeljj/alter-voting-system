<?php
session_start();
include 'db_connect.php'; // Database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful. You can now log in.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - VoteSecure</title>
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
        
        input[type="text"], input[type="password"], input[type="email"] {
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
        
        .register-btn {
            width: 100%;
            padding: 12px;
            background-color: #121826;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 15px;
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
        
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
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
            <p class="subtitle">Create your account to access the secure voting platform</p>
            
            <div class="tab-container">
                <div class="tab" onclick="window.location.href='login.php'">Login</div>
                <div class="tab active">Register</div>
            </div>
            
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <input type="text" id="username" name="username" placeholder="Choose a username" required>
                        <i class="input-icon fas fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-with-icon">
                        <input type="email" id="email" name="email" placeholder="Your email address" required>
                        <i class="input-icon far fa-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <i class="input-icon fas fa-lock"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <i class="input-icon fas fa-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="register-btn">Create Account</button>
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
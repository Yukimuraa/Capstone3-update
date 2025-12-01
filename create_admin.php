<?php
// This script should be deleted after use for security reasons
session_start();
require_once 'config/database.php';
require_once 'config/email_config.php';
require_once './PHPMailer/src/Exception.php';
require_once './PHPMailer/src/PHPMailer.php';
require_once './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$emailConfig = include 'config/email_config.php';

$error = '';
$success = '';
$showOtpField = false;

// Password validation function
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

// Handle OTP resend
if (isset($_POST['resend_otp'])) {
    if (isset($_SESSION['temp_admin'])) {
        $tempAdmin = $_SESSION['temp_admin'];
        $email = $tempAdmin['email'];
        
        // Generate new OTP
        $otp = rand(100000, 999999);
        $otpExpiry = time() + ($emailConfig['otp']['expiry_minutes'] * 60);
        
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = $otpExpiry;
        
        // Send new OTP via email
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = $emailConfig['smtp']['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $emailConfig['smtp']['username'];
            $mail->Password   = $emailConfig['smtp']['password'];
            $mail->SMTPSecure = $emailConfig['smtp']['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $emailConfig['smtp']['port'];
            $mail->SMTPDebug  = $emailConfig['smtp']['debug'];
            
            $mail->setFrom($emailConfig['smtp']['from_email'], $emailConfig['smtp']['from_name']);
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for Admin Account Creation';
            $mail->Body    = "Your OTP code is: <b>$otp</b><br>This code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";
            $mail->AltBody = "Your OTP code is: $otp\nThis code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";
            
            $mail->send();
            $success = "check your email to see otp code again";
            $showOtpField = true;
        } catch (Exception $e) {
            $error = "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Session expired. Please start over.";
        $showOtpField = false;
    }
// Handle OTP verification
} elseif (isset($_POST['verify_otp'])) {
    $userOtp = $_POST['otp'] ?? '';
    $storedOtp = $_SESSION['otp'] ?? '';
    $otpExpiry = $_SESSION['otp_expiry'] ?? 0;

    if (empty($userOtp)) {
        $error = "Please enter the OTP";
        $showOtpField = true;
    } elseif (time() > $otpExpiry) {
        $error = "OTP has expired. Please request a new one.";
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_admin']);
    } elseif ($userOtp != $storedOtp) {
        $error = "Invalid OTP. Please try again.";
        $showOtpField = true;
    } else {
        // OTP verified, check if email is still available before creating account
        $tempAdmin = $_SESSION['temp_admin'];
        $email = $tempAdmin['email'];
        
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already taken";
            $showOtpField = false;
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_admin']);
        } else {
            // Create admin account
            $hashed_password = password_hash($tempAdmin['password'], PASSWORD_DEFAULT);
            $user_type = $tempAdmin['user_type'] ?? 'admin';
            $stmt = $conn->prepare("INSERT INTO user_accounts (name, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $tempAdmin['name'], $tempAdmin['email'], $hashed_password, $user_type);

            if ($stmt->execute()) {
                unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_admin']);
                // Redirect to admin login page after successful account creation
                header("Location: admin_login.php?success=1");
                exit();
            } else {
                $error = "Error creating admin user: " . $conn->error;
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($user_type)) {
        $error = "All fields are required";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Full name must contain only letters and spaces";
    } elseif (!in_array($user_type, ['admin', 'secretary'])) {
        $error = "Invalid user type selected";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Validate password strength
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            $error = implode(". ", $passwordErrors);
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already taken";
                $showOtpField = false;
            } else {
                // Generate OTP
                $otp = rand(100000, 999999);
                $otpExpiry = time() + ($emailConfig['otp']['expiry_minutes'] * 60);

                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expiry'] = $otpExpiry;
                $_SESSION['temp_admin'] = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'user_type' => $user_type
                ];

                // Send OTP via email
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = $emailConfig['smtp']['host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $emailConfig['smtp']['username'];
                    $mail->Password   = $emailConfig['smtp']['password'];
                    $mail->SMTPSecure = $emailConfig['smtp']['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = $emailConfig['smtp']['port'];
                    $mail->SMTPDebug  = $emailConfig['smtp']['debug'];

                    $mail->setFrom($emailConfig['smtp']['from_email'], $emailConfig['smtp']['from_name']);
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Your OTP for Admin Account Creation';
                    $mail->Body    = "Your OTP code is: <b>$otp</b><br>This code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";
                    $mail->AltBody = "Your OTP code is: $otp\nThis code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";

                    $mail->send();
                    $success = "OTP has been sent to your email. Please check your inbox.";
                    $showOtpField = true;
                } catch (Exception $e) {
                    $error = "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
                    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_admin']);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - CHMSU BAO</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-image: url('image/ChamsuBackround.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        .split-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.85) 0%, rgba(185, 28, 28, 0.9) 100%);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('image/ChamsuBackround.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.3;
            z-index: 0;
        }
        .left-panel-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 500px;
        }
        .right-panel {
            flex: 1;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            max-height: 100vh;
        }
        .form-wrapper {
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }
        .submit-btn {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6);
        }
        .input-with-icon {
            position: relative;
            width: 100%;
        }
        .input-with-icon .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
            pointer-events: none;
            z-index: 10;
        }
        .input-with-icon input,
        .input-with-icon select {
            padding-left: 45px !important;
            padding-right: 50px !important;
            width: 100%;
            box-sizing: border-box;
        }
        .input-with-icon input[type="password"],
        .input-with-icon input[type="text"] {
            padding-right: 50px !important;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            clear: both;
        }
        .password-strength.weak {
            background: #ef4444;
            width: 33%;
        }
        .password-strength.medium {
            background: #f59e0b;
            width: 66%;
        }
        .password-strength.strong {
            background: #10b981;
            width: 100%;
        }
        .requirement {
            font-size: 0.75rem;
            margin-top: 6px;
            margin-bottom: 4px;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            line-height: 1.4;
        }
        .requirement.met {
            color: #10b981;
        }
        .requirement.unmet {
            color: #6b7280;
        }
        .otp-input {
            letter-spacing: 8px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }
        input, select {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        input:focus, select:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #DC2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        label {
            color: #374151;
            font-weight: 600;
        }
        .sliding-text {
            position: relative;
            width: 100%;
            overflow: hidden;
            height: 60px;
        }
        .sliding-text h1 {
            display: inline-block;
            animation: slideRepeat 10s linear infinite;
            white-space: nowrap;
            padding-left: 100%;
        }
        @keyframes slideRepeat {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        @media (max-width: 768px) {
            .split-container {
                flex-direction: column;
            }
            .left-panel {
                min-height: 200px;
            }
            .right-panel {
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <!-- Left Panel - Visual Content -->
        <div class="left-panel">
            <div class="left-panel-content">
                <div class="mb-6">
                    <div class="bg-white rounded-full p-4 shadow-2xl inline-block">
                        <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="100px" height="100px">
                    </div>
                </div>
                <div class="sliding-text mb-4">
                    <h1 class="text-4xl font-bold">Welcome To CHMSU TALISAY</h1>
                </div>
                <h2 class="text-2xl font-semibold mb-4">Business Affairs Office</h2>
                <p class="text-lg opacity-90">Create your admin account with secure OTP verification to manage the BAO system.</p>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="right-panel">
            <div class="form-wrapper">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Create Admin Account</h2>
                    <p class="text-gray-600">Secure admin account creation with OTP verification</p>
                </div>
                

            <?php if ($showOtpField): ?>
                <!-- OTP Verification Form -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                        <p><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                        <i class="fas fa-envelope-open-text text-blue-600 text-4xl mb-2"></i>
                        <p class="text-sm text-gray-700">Check your email for the 6-digit OTP code</p>
                    </div>
                </div>
                <form method="POST" id="otpForm">
                    <div class="mb-6">
                        <label for="otp" class="block text-gray-700 font-semibold mb-3">
                            <i class="fas fa-key mr-2"></i>Enter OTP Code
                        </label>
                        <input type="text" id="otp" name="otp" class="otp-input w-full px-4 py-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required style="box-sizing: border-box;">
                        <p class="text-xs text-gray-500 mt-3 text-center">Enter the 6-digit code sent to your email</p>
                    </div>
                    <button type="submit" name="verify_otp" class="w-full submit-btn text-white py-3 px-4 rounded-lg font-semibold focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        <i class="fas fa-check-circle mr-2"></i>Verify OTP
                    </button>
                </form>
                <div class="text-center mt-4">
                    <form method="POST" class="inline">
                        <p class="text-sm text-gray-600">
                            Didn't receive the code? 
                            <button type="submit" name="resend_otp" class="text-red-600 hover:underline font-semibold bg-transparent border-none cursor-pointer p-0">Resend</button>
                        </p>
                    </form>
                </div>
            <?php else: ?>
                <!-- Registration Form -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                        <p><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="adminForm">
                    <div class="mb-5">
                        <label for="user_type" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-user-tag mr-2"></i>User Type
                        </label>
                        <div class="input-with-icon" style="position: relative; width: 100%;">
                            <span class="icon">
                                <i class="fas fa-user-tag"></i>
                            </span>
                            <select id="user_type" name="user_type" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                                <option value="">Select user type</option>
                                <option value="admin">BAO Admin</option>
                                <option value="secretary">BAO Secretary</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="name" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-user mr-2"></i>Full Name
                        </label>
                        <div class="input-with-icon" style="position: relative; width: 100%;">
                            <span class="icon">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" id="name" name="name" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" placeholder="Enter full name" pattern="[a-zA-Z\s]+" title="Only letters and spaces allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')" required>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="email" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email Address
                        </label>
                        <div class="input-with-icon" style="position: relative; width: 100%;">
                            <span class="icon">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" id="email" name="email" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" placeholder="admin@chmsu.edu.ph" required>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="password" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <div class="input-with-icon" style="position: relative; width: 100%;">
                            <span class="icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" placeholder="Create a strong password" required>
                            <button type="button" onclick="togglePassword('password', 'togglePasswordIcon1')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280; z-index: 20; padding: 5px;">
                                <i class="fas fa-eye" id="togglePasswordIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="mt-3 space-y-1" id="passwordRequirements">
                            <div class="requirement unmet" id="req-length">
                                <i class="fas fa-circle text-xs mr-2"></i>At least 8 characters
                            </div>
                            <div class="requirement unmet" id="req-uppercase">
                                <i class="fas fa-circle text-xs mr-2"></i>One uppercase letter
                            </div>
                            <div class="requirement unmet" id="req-lowercase">
                                <i class="fas fa-circle text-xs mr-2"></i>One lowercase letter
                            </div>
                            <div class="requirement unmet" id="req-number">
                                <i class="fas fa-circle text-xs mr-2"></i>One number
                            </div>
                            <div class="requirement unmet" id="req-special">
                                <i class="fas fa-circle text-xs mr-2"></i>One special character
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirm Password
                        </label>
                        <div class="input-with-icon" style="position: relative; width: 100%;">
                            <span class="icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" placeholder="Confirm your password" required>
                            <button type="button" onclick="togglePassword('confirm_password', 'togglePasswordIcon2')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280; z-index: 20; padding: 5px;">
                                <i class="fas fa-eye" id="togglePasswordIcon2"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" class="mt-2 text-sm min-h-[20px]"></div>
                    </div>

                    <button type="submit" name="create_admin" class="w-full submit-btn text-white py-3 px-4 rounded-lg font-semibold focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        <i class="fas fa-user-plus mr-2"></i>Create Admin Account
                    </button>

                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            This page should be deleted after creating the admin account
                        </p>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-6 pt-6 border-t border-gray-200">
                <p class="text-gray-600 text-sm">
                    Already have an account? 
                    <a href="admin_login.php" class="text-red-600 hover:text-red-700 font-semibold hover:underline">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login here
                    </a>
                </p>
            </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update requirement indicators
            document.getElementById('req-length').classList.toggle('met', requirements.length);
            document.getElementById('req-length').classList.toggle('unmet', !requirements.length);
            document.getElementById('req-uppercase').classList.toggle('met', requirements.uppercase);
            document.getElementById('req-uppercase').classList.toggle('unmet', !requirements.uppercase);
            document.getElementById('req-lowercase').classList.toggle('met', requirements.lowercase);
            document.getElementById('req-lowercase').classList.toggle('unmet', !requirements.lowercase);
            document.getElementById('req-number').classList.toggle('met', requirements.number);
            document.getElementById('req-number').classList.toggle('unmet', !requirements.number);
            document.getElementById('req-special').classList.toggle('met', requirements.special);
            document.getElementById('req-special').classList.toggle('unmet', !requirements.special);

            // Calculate strength
            Object.values(requirements).forEach(req => {
                if (req) strength++;
            });

            // Update strength bar
            const strengthBar = document.getElementById('passwordStrength');
            strengthBar.className = 'password-strength';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }

            if (password === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-2"></i><span class="text-green-600">Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle text-red-600 mr-2"></i><span class="text-red-600">Passwords do not match</span>';
            }
        }

        // Event listeners
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });

        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // OTP input formatting
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    </script>
</body>
</html>

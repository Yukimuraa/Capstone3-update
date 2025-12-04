<?php
session_start();
require_once 'config/database.php';
require_once 'config/email_config.php';
require_once 'includes/functions.php';
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$emailConfig = include 'config/email_config.php';

$error = '';
$success = '';
$showOtpField = false;
$resendCooldown = 60; // 60 seconds cooldown between resends


if (isset($_POST['resend_otp'])) {
    // Check if temp_user exists in session
    if (!isset($_SESSION['temp_user'])) {
        $error = "Session expired. Please register again.";
        $showOtpField = false;
    } else {
        // Check rate limiting - prevent resending too frequently
        $lastResendTime = $_SESSION['last_otp_resend'] ?? 0;
        $timeSinceLastResend = time() - $lastResendTime;
        
        if ($timeSinceLastResend < $resendCooldown) {
            $remainingTime = $resendCooldown - $timeSinceLastResend;
            $error = "Please wait {$remainingTime} seconds before requesting a new OTP.";
            $showOtpField = true;
        } else {
            // Generate new OTP
            $otp = rand(100000, 999999);
            $otpExpiry = time() + ($emailConfig['otp']['expiry_minutes'] * 60);
            
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = $otpExpiry;
            $_SESSION['last_otp_resend'] = time();
            
            $email = $_SESSION['temp_user']['email'];
            
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
                $mail->Subject = 'Your New OTP for Registration';
                $mail->Body    = "Your new OTP code is: <b>$otp</b><br>This code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";
                $mail->AltBody = "Your new OTP code is: $otp\nThis code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";
                
                $mail->send();
                $success = "A new OTP has been sent to your email. Please check your inbox.";
                $showOtpField = true;
            } catch (Exception $e) {
                $error = "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
                $showOtpField = true;
            }
        }
    }
} elseif (isset($_POST['verify_otp'])) {
    $userOtp = $_POST['otp'] ?? '';
    $storedOtp = $_SESSION['otp'] ?? '';
    $otpExpiry = $_SESSION['otp_expiry'] ?? 0;

    if (empty($userOtp)) {
        $error = "Please enter the OTP";
        $showOtpField = true;
    } elseif (time() > $otpExpiry) {
        $error = "OTP has expired. Please request a new one.";
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user'], $_SESSION['last_otp_resend']);
    } elseif ($userOtp != $storedOtp) {
        $error = "Invalid OTP. Please try again.";
        $showOtpField = true;
    } else {

        $tempUser = $_SESSION['temp_user'];
        $hashed_password = password_hash($tempUser['password'], PASSWORD_DEFAULT);

        // Check if role column exists
        $check_role = $conn->query("SHOW COLUMNS FROM user_accounts LIKE 'role'");
        $role_exists = $check_role->num_rows > 0;
        
        if ($role_exists) {
            // Insert with role field
            $stmt = $conn->prepare("INSERT INTO user_accounts (name, email, password, user_type, role, organization) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $tempUser['name'], $tempUser['email'], $hashed_password, $tempUser['user_type'], $tempUser['role'], $tempUser['organization']);
        } else {
            // Insert without role field (backward compatibility)
        $stmt = $conn->prepare("INSERT INTO user_accounts (name, email, password, user_type, organization) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $tempUser['name'], $tempUser['email'], $hashed_password, $tempUser['user_type'], $tempUser['organization']);
        }

        if ($stmt->execute()) {
            $success = "Registration successful! Redirecting to login...";
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user'], $_SESSION['last_otp_resend']);
            header("refresh:2; url=login.php");
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    $role = $_POST['role'] ?? '';
    $role_index = $_POST['role_index'] ?? '';
    $user_type_index = $_POST['user_type_index'] ?? '';
    $organization = $_POST['organization'] ?? '';
    
    // Determine role from selected index (most reliable method)
    // selectedIndex: 1 = Student, 2 = Faculty, 3 = Staff, 4 = External
    // Note: selectedIndex is 0-based, but we subtract 1 because option 0 is "-- Select --"
    if ($user_type === 'student' && $user_type_index !== '' && $user_type_index !== null) {
        // Convert to string and map: 1=Student, 2=Faculty, 3=Staff
        $index_role_map = ['1' => 'student', '2' => 'faculty', '3' => 'staff', 1 => 'student', 2 => 'faculty', 3 => 'staff'];
        $determined_role = $index_role_map[$user_type_index] ?? null;
        if ($determined_role) {
            $role = $determined_role;
        }
    }
    
    // Fallback: If role is empty but role_index is set, determine role from role_index
    if ($user_type === 'student' && empty($role) && !empty($role_index)) {
        $role_map = ['1' => 'student', '2' => 'faculty', '3' => 'staff'];
        $role = $role_map[$role_index] ?? 'student';
    }
    
    // Final fallback: default to student
    if ($user_type === 'student' && empty($role)) {
        $role = 'student';
    }

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($user_type)) {
        $error = "All fields are required";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Full name must contain only letters and spaces (no numbers or symbols)";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($user_type === 'external' && empty($organization)) {
        $error = "Organization name is required for external users";
    } elseif ($user_type === 'student' && empty($role)) {
        $error = "Please select Student, Faculty, or Staff";
    } else {
        $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            $otp = rand(100000, 999999);
            $otpExpiry = time() + ($emailConfig['otp']['expiry_minutes'] * 60);

            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = $otpExpiry;
            $_SESSION['last_otp_resend'] = time(); // Set initial resend time
            
            // Ensure role is set for student type users
            if ($user_type === 'student' && empty($role)) {
                // Try user_type_index first (most reliable)
                if ($user_type_index !== '' && $user_type_index !== null) {
                    $index_role_map = ['1' => 'student', '2' => 'faculty', '3' => 'staff', 1 => 'student', 2 => 'faculty', 3 => 'staff'];
                    $role = $index_role_map[$user_type_index] ?? 'student';
                }
                // Try role_index as fallback
                elseif (!empty($role_index)) {
                    $role_map = ['1' => 'student', '2' => 'faculty', '3' => 'staff'];
                    $role = $role_map[$role_index] ?? 'student';
                } else {
                    $role = 'student'; // Default fallback
                }
            }
            
            // Debug: Log registration data (remove in production)
            error_log("Registration Data - Name: $name, Email: $email, User Type: $user_type, Role: $role, Role Index: $role_index, User Type Index: $user_type_index");
            
            // Temporary debug output (remove after testing)
            if (!empty($_POST)) {
                error_log("POST Data: " . print_r($_POST, true));
            }
            
            $_SESSION['temp_user'] = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'user_type' => $user_type,
                'role' => $role,
                'organization' => $organization
            ];

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
                $mail->Subject = 'Your OTP for Registration';
                $mail->Body    = "Your OTP code is: <b>$otp</b><br>This code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";
                $mail->AltBody = "Your OTP code is: $otp\nThis code will expire in {$emailConfig['otp']['expiry_minutes']} minutes.";

                $mail->send();
                $success = "OTP has been sent to your email. Please check your inbox.";
                $showOtpField = true;
            } catch (Exception $e) {
                $error = "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
                unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user'], $_SESSION['last_otp_resend']);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register - CHMSU</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            background: linear-gradient(135deg, rgba(0, 100, 0, 0.85) 0%, rgba(0, 80, 0, 0.9) 100%);
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
        .register-btn {
            background: linear-gradient(135deg, #1E40AF 0%, #1E3A8A 100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.4);
        }
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.6);
        }
        input, select {
            padding-left: 15px !important;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        input:focus, select:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #1E40AF;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
        label {
            color: #374151;
            font-weight: 600;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #DC2626;
        }
        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            clear: both;
            background: #e5e7eb;
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
        .sliding-text {
            overflow: hidden;
            white-space: nowrap;
            display: inline-block;
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
    <script>
        function toggleOrganizationField() {
            const userType = document.getElementById("user_type").value;
            const orgField = document.getElementById("organization_field");
            orgField.classList.toggle("hidden", userType !== "external");
        }
        
        // Update role field based on selected option
        function updateRoleField() {
            const userTypeSelect = document.getElementById("user_type");
            if (!userTypeSelect) return;
            
            const selectedIndex = userTypeSelect.selectedIndex;
            if (selectedIndex < 0) return;
            
            const selectedOption = userTypeSelect.options[selectedIndex];
            const roleField = document.getElementById("role");
            
            if (!roleField) return;
            
            // Get the role from data-role attribute
            let role = selectedOption.getAttribute("data-role") || "";
            const roleIndex = selectedOption.getAttribute("data-index") || "";
            
            // Fallback: determine role from option text if data-role is empty
            if (!role && selectedOption.text) {
                const optionText = selectedOption.text.trim().toLowerCase();
                if (optionText === "student") role = "student";
                else if (optionText === "faculty") role = "faculty";
                else if (optionText === "staff") role = "staff";
            }
            
            roleField.value = role;
            
            // Also set role_index as backup
            const roleIndexField = document.getElementById("role_index");
            if (roleIndexField) {
                roleIndexField.value = roleIndex;
            }
            
            // Debug
            console.log("Selected option:", selectedOption.text, "Role set to:", role, "Index:", roleIndex);
        }
        
        // Set the selected index as a separate field (most reliable)
        function setRoleIndex() {
            const userTypeSelect = document.getElementById("user_type");
            const indexField = document.getElementById("user_type_index");
            if (userTypeSelect && indexField) {
                const selectedIndex = userTypeSelect.selectedIndex;
                const selectedOption = userTypeSelect.options[selectedIndex];
                
                // Use data-index attribute if available, otherwise use selectedIndex
                const dataIndex = selectedOption ? selectedOption.getAttribute("data-index") : null;
                const indexToUse = dataIndex || selectedIndex.toString();
                
                indexField.value = indexToUse;
                console.log("Selected index (selectedIndex):", selectedIndex, "data-index:", dataIndex, "Setting to:", indexToUse);
            }
        }
        
        // Ensure role is set when page loads if dropdown has a value
        document.addEventListener('DOMContentLoaded', function() {
            updateRoleField();
            setRoleIndex();
        });
        
        // Force update before form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Update both fields immediately before submission
                    updateRoleField();
                    setRoleIndex();
                    
                    // Double-check role is set
                    const roleField = document.getElementById("role");
                    const userType = document.getElementById("user_type").value;
                    if (userType === "student" && (!roleField || !roleField.value)) {
                        console.warn("Role still empty! Using index fallback...");
                        setRoleIndex();
                    }
                }, false); // Use capture phase to run before validation
            }
        });
    </script>
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
                <p class="text-lg opacity-90">Create your account to access our services and manage your requests efficiently.</p>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="right-panel">
            <div class="form-wrapper">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Create Account</h2>
                    <p class="text-gray-600">Register to access the Business Affairs Office system</p>
                </div>
            <?php if (!empty($error)): ?>
                <div class="error-message px-4 py-3 rounded-lg mb-6 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message px-4 py-3 rounded-lg mb-6 text-sm">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($showOtpField): ?>
                <form method="POST" class="mt-6" id="otpForm">
                    <div class="mb-6">
                        <label for="otp" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-key mr-2"></i>Enter OTP Code
                        </label>
                        <input type="text" id="otp" name="otp" class="w-full px-4 py-3 rounded-lg" placeholder="6-digit OTP" maxlength="6" pattern="[0-9]{6}" required style="text-align: center; font-size: 24px; letter-spacing: 8px; font-weight: bold;">
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            <i class="fas fa-info-circle mr-1"></i>Didn't receive the code? Check your spam folder or resend below.
                        </p>
                    </div>
                    <button type="submit" name="verify_otp" class="w-full register-btn text-white py-3 px-4 rounded-lg font-semibold mb-3">
                        <i class="fas fa-check-circle mr-2"></i>Verify OTP
                    </button>
                </form>
                    <div class="text-center">
                        <form method="POST" style="display: inline;" id="resendOtpForm" novalidate>
                            <button type="submit" name="resend_otp" id="resendOtpBtn" class="text-blue-600 hover:text-blue-700 font-semibold text-sm disabled:text-gray-400 disabled:cursor-not-allowed" disabled>
                                <i class="fas fa-redo mr-1"></i><span id="resendText">Resend OTP</span> <span id="resendTimer" class="hidden"></span>
                            </button>
                        </form>
                    </div>
            <?php else: ?>
                <form method="POST" class="mt-6" onsubmit="updateRoleField(); return true;">
                    <div class="mb-4">
                        <label for="user_type" class="block text-gray-700 font-semibold mb-2">User Type</label>
                        <select id="user_type" name="user_type" onchange="toggleOrganizationField(); updateRoleField(); setRoleIndex();" class="w-full px-4 py-3 rounded-lg" required>
                            <option value="">-- Select User Type --</option>
                            <option value="student" data-role="student" data-index="1">Student</option>
                            <option value="student" data-role="faculty" data-index="2">Faculty</option>
                            <option value="student" data-role="staff" data-index="3">Staff</option>
                            <option value="external" data-role="" data-index="4">External User</option>
                        </select>
                        <input type="hidden" id="role" name="role" value="">
                        <input type="hidden" id="role_index" name="role_index" value="">
                        <input type="hidden" id="user_type_index" name="user_type_index" value="">
                    </div>
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 font-semibold mb-2">Full Name</label>
                        <input type="text" name="name" id="name" class="w-full px-4 py-3 rounded-lg" required 
                               pattern="[a-zA-Z\s]+" 
                               title="Full name must contain only letters and spaces (no numbers or symbols)"
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                    </div>
                    <div id="organization_field" class="mb-4 hidden">
                        <label for="organization" class="block text-gray-700 font-semibold mb-2">Organization</label>
                        <input type="text" name="organization" class="w-full px-4 py-3 rounded-lg">
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                        <input type="email" name="email" class="w-full px-4 py-3 rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" class="w-full px-4 py-3 rounded-lg" style="padding-right: 50px;" required onkeyup="checkPasswordStrength(); checkPasswordMatch();">
                            <button type="button" onclick="togglePassword('password', 'togglePasswordIcon1')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280;">
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
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-3 rounded-lg" style="padding-right: 50px;" required onkeyup="checkPasswordMatch();">
                            <button type="button" onclick="togglePassword('confirm_password', 'togglePasswordIcon2')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280;">
                                <i class="fas fa-eye" id="togglePasswordIcon2"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" class="mt-2 text-sm min-h-[20px]"></div>
                    </div>
                    <button type="submit" name="register" class="w-full register-btn text-white py-3 px-4 rounded-lg font-semibold">
                        <i class="fas fa-user-plus mr-2"></i>Register
                    </button>
                </form>
            <?php endif; ?>
            
                <div class="text-center mt-6 pt-6 border-t border-gray-200">
                    <p class="text-gray-600 text-sm">
                        Already have an account? 
                        <a href="login.php" class="text-blue-600 hover:text-blue-700 font-semibold hover:underline">Login here</a>
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

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
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
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength();
                checkPasswordMatch();
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        }

        // Resend OTP countdown timer
        <?php if ($showOtpField): ?>
        (function() {
            const resendBtn = document.getElementById('resendOtpBtn');
            const resendForm = document.getElementById('resendOtpForm');
            const resendText = document.getElementById('resendText');
            const resendTimer = document.getElementById('resendTimer');
            const cooldownSeconds = <?php echo $resendCooldown; ?>;
            const lastResendTime = <?php echo $_SESSION['last_otp_resend'] ?? 0; ?>;
            const currentTime = <?php echo time(); ?>;
            const timeSinceLastResend = currentTime - lastResendTime;
            
            // Prevent form validation on resend form
            if (resendForm) {
                resendForm.addEventListener('submit', function(e) {
                    // Allow form to submit normally, but prevent validation
                    e.stopPropagation();
                });
            }
            
            if (timeSinceLastResend < cooldownSeconds) {
                let remainingTime = cooldownSeconds - timeSinceLastResend;
                resendBtn.disabled = true;
                resendText.classList.add('hidden');
                resendTimer.classList.remove('hidden');
                
                const updateTimer = () => {
                    if (remainingTime > 0) {
                        const minutes = Math.floor(remainingTime / 60);
                        const seconds = remainingTime % 60;
                        resendTimer.textContent = `(${minutes}:${seconds.toString().padStart(2, '0')})`;
                        remainingTime--;
                        setTimeout(updateTimer, 1000);
                    } else {
                        resendBtn.disabled = false;
                        resendText.classList.remove('hidden');
                        resendTimer.classList.add('hidden');
                    }
                };
                
                updateTimer();
            } else {
                resendBtn.disabled = false;
            }
        })();
        <?php endif; ?>
    </script>
</body>

</html>

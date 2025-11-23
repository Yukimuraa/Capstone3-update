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

// Handle OTP verification
if (isset($_POST['verify_otp'])) {
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
        // OTP verified, create admin account
        $tempAdmin = $_SESSION['temp_admin'];
        $hashed_password = password_hash($tempAdmin['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO user_accounts (name, email, password, user_type) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param("sss", $tempAdmin['name'], $tempAdmin['email'], $hashed_password);

        if ($stmt->execute()) {
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_admin']);
            // Redirect to admin login page after successful account creation
            header("Location: admin_login.php?success=1");
            exit();
        } else {
            $error = "Error creating admin user: " . $conn->error;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Full name must contain only letters and spaces";
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
                $error = "Email already exists";
            } else {
                // Generate OTP
                $otp = rand(100000, 999999);
                $otpExpiry = time() + ($emailConfig['otp']['expiry_minutes'] * 60);

                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expiry'] = $otpExpiry;
                $_SESSION['temp_admin'] = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password
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
        body {
            background-image: url('image/ChamsuBackround.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .header-section {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            border-radius: 20px 20px 0 0;
        }
        .submit-btn {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
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
        .security-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="card w-full max-w-md overflow-hidden">
        <div class="header-section p-6 text-center text-white">
            <div class="flex justify-center mb-3">
                <div class="bg-white rounded-full p-2">
                    <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="40px" height="40px">
                </div>
            </div>
            <h2 class="text-2xl font-bold">Create Admin Account</h2>
        </div>

        <div class="p-8">
            <?php if (isset($error)): ?>
                <div class="text-red-700 text-sm mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="text-green-700 text-sm mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($showOtpField): ?>
                <!-- OTP Verification Form -->
                <div class="text-center mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                        <i class="fas fa-envelope-open-text text-blue-600 text-4xl mb-2"></i>
                        <p class="text-sm text-gray-700">Check your email for the 6-digit OTP code</p>
                    </div>
                </div>
                <form method="POST">
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
                    <div class="text-center mt-4">
                        <p class="text-sm text-gray-600">
                            Didn't receive the code? 
                            <a href="admin_forgot-password.php" class="text-red-600 hover:underline font-semibold">Resend</a>
                        </p>
                    </div>
                </form>
            <?php else: ?>
                <!-- Registration Form -->
                <form method="POST" id="adminForm">
                    <div class="mb-5">
                        <label for="name" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-user mr-2"></i>Full Name
                        </label>
                        <div class="input-with-icon" style="position: relative; width: 100%;">
                            <span class="icon">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" id="name" name="name" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" placeholder="Enter full name" pattern="[a-zA-Z\s]+" title="Only letters and spaces allowed" required>
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

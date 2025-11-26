<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$valid_token = false;
$email = '';
$is_admin_user = false; // Track if user is admin/secretary

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token - check if it exists first
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reset_data = $result->fetch_assoc();
        
        // Check if token has expired (if expires_at is set)
        if ($reset_data['expires_at'] !== null) {
            // Token has expiration date, check if it's still valid
            if (strtotime($reset_data['expires_at']) > time()) {
                $valid_token = true;
                $email = $reset_data['email'];
            } else {
                $error = "This password reset link has expired. Please request a new one.";
            }
        } else {
            // Old token without expiration - accept it but warn
            $valid_token = true;
            $email = $reset_data['email'];
        }
        
        // Check if user is admin or secretary
        if ($valid_token && !empty($email)) {
            $stmt = $conn->prepare("SELECT user_type FROM user_accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user_result = $stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $is_admin_user = ($user_data['user_type'] === 'admin' || $user_data['user_type'] === 'secretary');
            }
        }
    } else {
        $error = "Invalid token. Please request a new password reset link.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Both password fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the user's password
        $stmt = $conn->prepare("UPDATE user_accounts SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Delete the used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $success = "Your password has been reset successfully. You can now login with your new password.";
        } else {
            $error = "Error updating password: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin_user ? 'Reset Password - Admin/Secretary' : 'Reset Password'; ?> - CHMSU Business Affairs Office</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background-image: url('image/ChamsuBackround.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            z-index: 0;
        }
        .reset-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border-radius: 20px;
            <?php if ($is_admin_user): ?>
            border-top: 4px solid #DC2626;
            <?php endif; ?>
            overflow: hidden;
        }
        .header-section {
            <?php if ($is_admin_user): ?>
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.9) 0%, rgba(185, 28, 28, 0.95) 100%);
            <?php else: ?>
            background: linear-gradient(135deg, rgba(0, 100, 0, 0.8) 0%, rgba(0, 80, 0, 0.9) 100%);
            <?php endif; ?>
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        .form-section {
            background: rgba(255, 255, 255, 0.95);
        }
        .submit-btn {
            <?php if ($is_admin_user): ?>
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
            <?php else: ?>
            background: linear-gradient(135deg, #1E40AF 0%, #1E3A8A 100%);
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.4);
            <?php endif; ?>
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            <?php if ($is_admin_user): ?>
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6);
            <?php else: ?>
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.6);
            <?php endif; ?>
        }
        .input-with-icon {
            position: relative;
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
        .input-with-icon input {
            padding-left: 45px !important;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .input-with-icon input:focus {
            background: rgba(255, 255, 255, 1);
            <?php if ($is_admin_user): ?>
            border-color: #DC2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            <?php else: ?>
            border-color: #1E40AF;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
            <?php endif; ?>
        }
        label {
            color: #374151;
            font-weight: 600;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #DC2626;
            backdrop-filter: blur(10px);
        }
        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="reset-container w-full max-w-md">
        <div class="header-section p-8 text-center text-white">
            <div class="flex justify-center mb-4">
                <div class="bg-white rounded-full p-3 shadow-lg">
                    <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="60px" height="60px">
                </div>
            </div>
            <h2 class="text-2xl font-bold mb-2">Reset Password</h2>
            <p class="text-sm opacity-90"><?php echo $is_admin_user ? 'Create a new password for your Admin/Secretary account' : 'Create a new password for your account'; ?></p>
        </div>

        <div class="form-section p-8">
            <?php if (!empty($error)): ?>
                <div class="error-message px-4 py-3 rounded-lg mb-6 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message px-4 py-3 rounded-lg mb-6 text-sm">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                    <p class="mt-3">
                        <a href="<?php echo $is_admin_user ? 'admin_login.php' : 'login.php'; ?>" class="font-semibold text-green-700 hover:underline">
                            <i class="fas fa-sign-in-alt mr-1"></i>Click here to login
                        </a>
                    </p>
                </div>
            <?php elseif ($valid_token): ?>
                <form action="reset-password.php?token=<?php echo $token; ?>" method="POST">
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-medium mb-2">New Password</label>
                        <div class="input-with-icon" style="position: relative;">
                            <span class="icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 <?php echo $is_admin_user ? 'focus:ring-red-500' : 'focus:ring-blue-500'; ?>" style="padding-right: 40px;" required>
                            <button type="button" onclick="togglePassword('password', 'togglePasswordIcon1')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280;">
                                <i class="fas fa-eye" id="togglePasswordIcon1"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                    </div>

                    <div class="mb-6">
                        <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm New Password</label>
                        <div class="input-with-icon" style="position: relative;">
                            <span class="icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 <?php echo $is_admin_user ? 'focus:ring-red-500' : 'focus:ring-blue-500'; ?>" style="padding-right: 40px;" required>
                            <button type="button" onclick="togglePassword('confirm_password', 'togglePasswordIcon2')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280;">
                                <i class="fas fa-eye" id="togglePasswordIcon2"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full submit-btn text-white py-3 px-4 rounded-lg font-semibold focus:outline-none focus:ring-2 <?php echo $is_admin_user ? 'focus:ring-red-500' : 'focus:ring-blue-500'; ?> focus:ring-offset-2">
                        <i class="fas fa-key mr-2"></i>Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-6 pt-6 border-t border-gray-200">
                <p class="text-gray-600 text-sm">
                    <a href="<?php echo $is_admin_user ? 'admin_login.php' : 'login.php'; ?>" class="<?php echo $is_admin_user ? 'text-red-600 hover:text-red-700' : 'text-blue-600 hover:text-blue-700'; ?> font-semibold hover:underline">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Login
                    </a>
                </p>
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
    </script>
</body>
</html>
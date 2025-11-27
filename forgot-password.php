<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $error = "Email is required";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            
            // Set expiration time (1 hour from now)
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing tokens for this email
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Store the token in the database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires_at);
            
            if ($stmt->execute()) {
                // Get the base path of the application
                $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
                    '://' . $_SERVER['HTTP_HOST'] . $base_path . '/reset-password.php?token=' . $token;

                // Load SMTP configuration
                $config = require __DIR__ . '/config/email_config.php';

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = $config['smtp']['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $config['smtp']['username'];
                    $mail->Password = $config['smtp']['password'];
                    $mail->SMTPSecure = $config['smtp']['encryption'];
                    $mail->Port = $config['smtp']['port'];
                    if (isset($config['smtp']['debug'])) {
                        $mail->SMTPDebug = (int)$config['smtp']['debug'];
                    }

                    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body =
                        '<p>You requested to reset your password for CHMSU Business Affairs Office.</p>' .
                        '<p>Click the link below to reset your password. This link will expire in 1 hour.</p>' .
                        '<p><a href="' . htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') . '</a></p>' .
                        '<p>If you did not request this, you can safely ignore this email.</p>';
                    $mail->AltBody =
                        "You requested to reset your password. Open this link (valid for 1 hour):\n" . $reset_link . "\nIf you did not request this, ignore this email.";

                    $mail->send();

                    // Generic message regardless of user existence
                    $success = "If your email is registered in our system, you will receive a password reset link shortly.";
                } catch (Exception $e) {
                    // Do not disclose email delivery issues to the user
                    $success = "If your email is registered in our system, you will receive a password reset link shortly.";
                }
            } else {
                $error = "Error generating reset token: " . $conn->error;
            }
        } else {
            // Don't reveal if the user exists or not for security reasons
            $success = "If your email is registered in our system, you will receive a password reset link shortly.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CHMSU Business Affairs Office</title>
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
            background: linear-gradient(135deg, rgba(0, 100, 0, 0.3) 0%, rgba(0, 80, 0, 0.4) 100%);
            z-index: 0;
        }
        .forgot-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border-radius: 20px;
            overflow: hidden;
        }
        .header-section {
            background: linear-gradient(135deg, rgba(0, 100, 0, 0.8) 0%, rgba(0, 80, 0, 0.9) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        .form-section {
            background: rgba(255, 255, 255, 0.95);
        }
        .submit-btn {
            background: linear-gradient(135deg, #1E40AF 0%, #1E3A8A 100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.4);
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.6);
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
        .input-with-icon input,
        .input-with-icon select {
            padding-left: 45px !important;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .input-with-icon input:focus,
        .input-with-icon select:focus {
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
    <div class="forgot-container w-full max-w-md">
        <div class="header-section p-8 text-center text-white">
            <div class="flex justify-center mb-4">
                <div class="bg-white rounded-full p-3 shadow-lg">
                    <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="60px" height="60px">
                </div>
            </div>
            <h2 class="text-2xl font-bold mb-2">Forgot Password</h2>
            <p class="text-sm opacity-90">Enter your email to receive a password reset link</p>
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
                </div>
                <div class="text-center mt-6 pt-6 border-t border-gray-200">
                    <p class="text-gray-600 text-sm">
                        Remember your password? 
                        <a href="login.php" class="text-blue-600 hover:text-blue-700 font-semibold hover:underline">Back to Login</a>
                    </p>
                </div>
            <?php else: ?>
            <form action="forgot-password.php" method="POST">
                <div class="mb-6">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                    <div class="input-with-icon">
                        <span class="icon">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your email" required>
                    </div>
                </div>

                <button type="submit" class="w-full submit-btn text-white py-3 px-4 rounded-lg font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
                </button>

                <div class="text-center mt-6 pt-6 border-t border-gray-200">
                    <p class="text-gray-600 text-sm">
                        Remember your password? 
                        <a href="login.php" class="text-blue-600 hover:text-blue-700 font-semibold hover:underline">Back to Login</a>
                    </p>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
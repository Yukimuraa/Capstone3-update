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
    $user_type = $_POST['user_type'] ?? '';

    if (empty($email) || empty($user_type)) {
        $error = "Email and user type are required";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ? AND user_type = ?");
        $stmt->bind_param("ss", $email, $user_type);
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
            background-color: #00008B; /* Dark blue background */
        }
        .header-section {
            background-color: #006400; /* Dark green header */
        }
        .submit-btn {
            background-color: #1E40AF; /* Blue button */
        }
        .submit-btn:hover {
            background-color: #1E3A8A;
        }
        /* Fix for icon and text overlap */
        .input-with-icon {
            position: relative;
        }
        .input-with-icon .icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
            pointer-events: none;
        }
        .input-with-icon input,
        .input-with-icon select {
            padding-left: 35px !important; /* Increased padding to prevent overlap */
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-md w-full max-w-md overflow-hidden">
        <div class="header-section p-6 text-center text-white">
            <div class="flex justify-center mb-4">
                <i class="fas fa-school text-yellow-400 text-4xl"></i>
            </div>
            <div class="flex justify-center mb-4">
                <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="70px" height="70px" class="mx-auto">
            </div>
            <h2 class="text-xl font-bold">Forgot Password</h2>
            <p class="text-sm">Enter your email to receive a password reset link</p>
        </div>

        <div class="p-6">
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                    <?php if (isset($reset_link)): ?>
                        <div class="mt-2 p-2 bg-gray-100 rounded text-sm overflow-x-auto">
                            <p>Reset Link (for demonstration only):</p>
                            <a href="<?php echo $reset_link; ?>" class="text-blue-600 break-all"><?php echo $reset_link; ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="forgot-password.php" method="POST">
                <div class="mb-4">
                    <label for="user_type" class="block text-gray-700 font-medium mb-2">User Type</label>
                    <div class="input-with-icon">
                        <span class="icon">
                            <i class="fas fa-user-tag"></i>
                        </span>
                        <select id="user_type" name="user_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="student">Student/Faculty/Staff</option>
                            <option value="external">External User</option>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                    <div class="input-with-icon">
                        <span class="icon">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your email" required>
                    </div>
                </div>

                <button type="submit" class="w-full submit-btn text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Send Reset Link
                </button>

                <div class="text-center mt-6">
                    <p class="text-gray-600">
                        Remember your password? 
                        <a href="login.php" class="text-blue-600 hover:underline">Back to Login</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
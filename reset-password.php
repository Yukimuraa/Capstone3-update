<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$valid_token = false;
$email = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $reset_data = $result->fetch_assoc();
        $email = $reset_data['email'];
    } else {
        $error = "Invalid or expired token. Please request a new password reset link.";
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
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
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
    <title>Reset Password - CHMSU Business Affairs Office</title>
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
        .input-with-icon input {
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
            <h2 class="text-xl font-bold">Reset Password</h2>
            <p class="text-sm">Create a new password for your account</p>
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
                    <p class="mt-2">
                        <a href="login.php" class="font-bold text-green-700 hover:underline">Click here to login</a>
                    </p>
                </div>
            <?php elseif ($valid_token): ?>
                <form action="reset-password.php?token=<?php echo $token; ?>" method="POST">
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-medium mb-2">New Password</label>
                        <div class="input-with-icon">
                            <span class="icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                    </div>

                    <div class="mb-6">
                        <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm New Password</label>
                        <div class="input-with-icon">
                            <span class="icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>

                    <button type="submit" class="w-full submit-btn text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-6">
                <p class="text-gray-600">
                    <a href="login.php" class="text-blue-600 hover:underline">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
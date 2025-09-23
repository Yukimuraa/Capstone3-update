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


if (isset($_POST['verify_otp'])) {
    $userOtp = $_POST['otp'] ?? '';
    $storedOtp = $_SESSION['otp'] ?? '';
    $otpExpiry = $_SESSION['otp_expiry'] ?? 0;

    if (empty($userOtp)) {
        $error = "Please enter the OTP";
        $showOtpField = true;
    } elseif (time() > $otpExpiry) {
        $error = "OTP has expired. Please request a new one.";
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user']);
    } elseif ($userOtp != $storedOtp) {
        $error = "Invalid OTP. Please try again.";
        $showOtpField = true;
    } else {

        $tempUser = $_SESSION['temp_user'];
        $hashed_password = password_hash($tempUser['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, organization) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $tempUser['name'], $tempUser['email'], $hashed_password, $tempUser['user_type'], $tempUser['organization']);

        if ($stmt->execute()) {
            $success = "Registration successful! Redirecting to login...";
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user']);
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
    $organization = $_POST['organization'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($user_type)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($user_type === 'external' && empty($organization)) {
        $error = "Organization name is required for external users";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
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
            $_SESSION['temp_user'] = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'user_type' => $user_type,
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
                unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['temp_user']);
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
    <script>
        function toggleOrganizationField() {
            const userType = document.getElementById("user_type").value;
            const orgField = document.getElementById("organization_field");
            orgField.classList.toggle("hidden", userType !== "external");
        }
    </script>
</head>

<body class="bg-blue-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-lg shadow-lg p-6">
        <div class="text-center">
            <img src="image/CHMSUWebLOGO.png" class="mx-auto mb-2" width="70" height="70">
            <p class="text-sm">Register to access the Business Affairs Office system</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 mt-4 rounded"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 mt-4 rounded">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($showOtpField): ?>
            <form method="POST" class="mt-6">
                <div class="mb-4">
                    <label for="otp" class="block text-gray-700 font-semibold mb-2">Enter OTP</label>
                    <input type="text" id="otp" name="otp" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="6-digit OTP" required>
                </div>
                <button type="submit" name="verify_otp" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">Verify OTP</button>
            </form>
        <?php else: ?>
            <form method="POST" class="mt-6">
                <div class="mb-4">
                    <label for="user_type" class="block text-gray-700 font-semibold mb-2">User Type</label>
                    <select id="user_type" name="user_type" onchange="toggleOrganizationField()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="student">Student / Faculty</option>
                        <option value="external">External Organization</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-semibold mb-2">Full Name</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div id="organization_field" class="mb-4 hidden">
                    <label for="organization" class="block text-gray-700 font-semibold mb-2">Organization</label>
                    <input type="text" name="organization" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <button type="submit" name="register" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">Register</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
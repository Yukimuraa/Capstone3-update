<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Check for success message from account creation
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Admin account created successfully! Please login with your credentials.";
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    if (empty($email) || empty($password) || empty($user_type)) {
        $error = "All fields are required";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ? AND user_type = ?");
        $stmt->bind_param("ss", $email, $user_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Initialize user_sessions array if it doesn't exist
                if (!isset($_SESSION['user_sessions'])) {
                    $_SESSION['user_sessions'] = [];
                }
                
                // Store user data in the session
                $_SESSION['user_sessions'][$user_type] = [
                    'user_id' => $user['id'],
                    'user_name' => $user['name'],
                    'user_email' => $user['email'],
                    'user_type' => $user['user_type']
                ];
                
                // Set this user type as active
                $_SESSION['active_user_type'] = $user_type;
                
                // Redirect based on user type
                switch ($user_type) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'secretary':
                        header("Location: admin/dashboard.php");
                        break;
                    default:
                        header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Admin Login - CHMSU Business Affairs Office</title>
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
     }
     .admin-header-section {
         background: rgba(0, 0, 0, 0.3);
         backdrop-filter: blur(5px);
     }
     .admin-login-btn {
         background-color: #DC2626; /* Red button for admin */
     }
     .admin-login-btn:hover {
         background-color: #B91C1C;
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
 <div class="bg-white rounded-lg shadow-md w-full max-w-md overflow-hidden" style="border-top: 4px solid #DC2626; background: rgba(255, 255, 255, 0.95);">
     <div class="admin-header-section p-6 text-center text-white">
        <div class="flex justify-center mb-4">
            <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="70px" height="70px" class="mx-auto">
        </div>
        <h2 class="text-s font-bold">BAO Admin & Secretary Login</h2>
        <p class="text-sm mt-2">Admin access and Secretary only</p>
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
             </div>
         <?php endif; ?>

         <form action="admin_login.php" method="POST">
             <div class="mb-4">
                 <label for="user_type" class="block text-gray-700 font-medium mb-2">User Type</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-user-tag"></i>
                     </span>
                     <select id="user_type" name="user_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" required>
                         <option value="admin">BAO Admin</option>
                         <option value="secretary">BAO Secretary</option>
                     </select>
                 </div>
             </div>

             <div class="mb-4">
                 <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-envelope"></i>
                     </span>
                     <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Enter your email" required>
                 </div>
             </div>

             <div class="mb-4">
                 <div class="flex items-center justify-between mb-2">
                     <label for="password" class="block text-gray-700 font-medium">Password</label>
                     <a href="admin_forgot-password.php" class="text-sm text-red-600 hover:underline">Forgot password?</a>
                 </div>
                 <div class="input-with-icon" style="position: relative;">
                     <span class="icon">
                         <i class="fas fa-lock"></i>
                     </span>
                     <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" style="padding-right: 40px;" required>
                     <button type="button" onclick="togglePassword('password', 'togglePasswordIcon')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280;">
                         <i class="fas fa-eye" id="togglePasswordIcon"></i>
                     </button>
                 </div>
             </div>

             <div class="mb-6 flex items-center">
                 <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                 <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
             </div>

            <button type="submit" class="w-full admin-login-btn text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <i class="fas fa-sign-in-alt mr-2"></i> Login
            </button>

            <div class="text-center mt-4">
                <p class="text-gray-600 text-sm mb-2">
                    Don't have an account?
                </p>
                <a href="create_admin.php" class="text-red-600 hover:text-red-700 hover:underline font-medium">
                    <i class="fas fa-user-plus mr-1"></i> Create Account
                </a>
            </div>
         </form>
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


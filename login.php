<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    if (empty($email) || empty($password) || empty($user_type)) {
        $error = "All fields are required";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_type = ?");
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
                    case 'staff':
                        header("Location: staff/dashboard.php");
                        break;
                    case 'student':
                        header("Location: student/dashboard.php");
                        break;
                    case 'external':
                        header("Location: external/dashboard.php");
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
 <title>Login - CHMSU Business Affairs Office</title>
 <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
 <link rel="stylesheet" href="assets/css/styles.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
 <style>
     body {
         background-color: #00008B; /* Dark blue background */
     }
     .header-section {
         background-color: #008000; /* Dark green header */
     }
     .login-btn {
         background-color: #1E40AF; /* Blue button */
     }
     .login-btn:hover {
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
         <!-- <div class="flex justify-center mb-4">
             <i class="fas fa-school text-yellow-400 text-4xl"></i>
         </div> -->
         <div class="flex justify-center mb-4">
             <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="70px" height="70px" class="mx-auto">
         </div>
         <h2 class="text-s font-bold">Enter your credentials to access the system</h2>
     </div>

     <div class="p-6">
         <?php if (!empty($error)): ?>
             <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                 <?php echo $error; ?>
             </div>
         <?php endif; ?>

         <form action="login.php" method="POST">
             <div class="mb-4">
                 <label for="user_type" class="block text-gray-700 font-medium mb-2">User Type</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-user-tag"></i>
                     </span>
                     <select id="user_type" name="user_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                         <option value="student">Student / Faculty</option>
                         <!-- <option value="staff">Staff</option> -->
                         <option value="admin">BAO Admin / Staff</option>
                         <option value="external">External User</option>
                     </select>
                 </div>
             </div>

             <div class="mb-4">
                 <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-envelope"></i>
                     </span>
                     <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your email" required>
                 </div>
             </div>

             <div class="mb-4">
                 <div class="flex items-center justify-between mb-2">
                     <label for="password" class="block text-gray-700 font-medium">Password</label>
                     <a href="forgot-password.php" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                 </div>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-lock"></i>
                     </span>
                     <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                 </div>
             </div>

             <div class="mb-6 flex items-center">
                 <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                 <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
             </div>

             <button type="submit" class="w-full login-btn text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                 Login
             </button>

             <div class="text-center mt-6">
                 <p class="text-gray-600">
                     Don't have an account? 
                     <a href="register.php" class="text-blue-600 hover:underline">Register</a>
                 </p>
             </div>
         </form>
     </div>
 </div>
</body>
</html>

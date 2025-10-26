<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
     // Check if email already exists
     $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE email = ?");
     $stmt->bind_param("s", $email);
     $stmt->execute();
     $result = $stmt->get_result();
     
     if ($result->num_rows > 0) {
         $error = "Email already exists";
     } else {
         // Hash password
         $hashed_password = password_hash($password, PASSWORD_DEFAULT);
         
         // Insert new user
         $stmt = $conn->prepare("INSERT INTO user_accounts (name, email, password, user_type, organization) VALUES (?, ?, ?, ?, ?)");
         $stmt->bind_param("sssss", $name, $email, $hashed_password, $user_type, $organization);
         
         if ($stmt->execute()) {
             $success = "Registration successful! You can now login.";
             if($success){
                header("refresh:3; url = login.php"); 
             }
            
         } else {
             $error = "Registration failed: " . $conn->error;
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
 <title>Register - CHMSU Business Affairs Office</title>
 <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
 <link rel="stylesheet" href="assets/css/styles.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
 <style>
     body {
         background-color: #00008B; /* Dark blue background */
     }
     .header-section {
         background-color: green; /* Dark green header */
     }
     .register-btn {
         background-color: #1E40AF; /* Blue button */
     }
     .register-btn:hover {
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
     <div class="header-section p-3 text-center text-white">
         <!-- <div class="flex justify-center mb-4">
             <i class="fas fa-school text-yellow-400 text-4xl"></i>
         </div> -->
         <div class="flex justify-center mb-4">
             <img src="image/CHMSUWebLOGO.png" alt="CHMSU Logo" width="70px" height="70px" class="mx-auto">
         </div>
         <!-- <h2 class="text-xl font-bold">Create an Account</h2> -->
         <p class="text-sm">Register to access the Business Affairs Office system</p>
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
         <?php endif; ?>

         <form action="register.php" method="POST">
             <div class="mb-4">
                 <label for="user_type" class="block text-gray-700 font-medium mb-2">User Type</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-user-tag"></i>
                     </span>
                     <select id="user_type" name="user_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleOrganizationField()">
                         <option value="student">Student</option>
                         <option value="staff">Staff</option>
                         <option value="external">External Organization</option>
                     </select>
                 </div>
             </div>

             <div class="mb-4">
                 <label for="name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-user"></i>
                     </span>
                     <input type="text" id="name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="John Doe" required>
                 </div>
             </div>

             <div id="organization_field" class="mb-4 hidden">
                 <label for="organization" class="block text-gray-700 font-medium mb-2">Organization Name</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-building"></i>
                     </span>
                     <input type="text" id="organization" name="organization" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Your Organization">
                 </div>
             </div>

             <div class="mb-4">
                 <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-envelope"></i>
                     </span>
                     <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="your.email@example.com" required>
                 </div>
             </div>

             <div class="mb-4">
                 <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-lock"></i>
                     </span>
                     <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                 </div>
             </div>

             <div class="mb-6">
                 <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                 <div class="input-with-icon">
                     <span class="icon">
                         <i class="fas fa-lock"></i>
                     </span>
                     <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                 </div>
             </div>

             <button type="submit" class="w-full register-btn text-white py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                 Register
             </button>

             <div class="text-center mt-4">
                 <p class="text-gray-600">
                     Already have an account? 
                     <a href="login.php" class="text-blue-600 hover:underline">Login</a>
                 </p>
             </div>
         </form>
     </div>
 </div>

 <script>
     function toggleOrganizationField() {
         const userType = document.getElementById('user_type').value;
         const organizationField = document.getElementById('organization_field');
         
         if (userType === 'external') {
             organizationField.classList.remove('hidden');
             document.getElementById('organization').setAttribute('required', 'required');
         } else {
             organizationField.classList.add('hidden');
             document.getElementById('organization').removeAttribute('required');
         }
     }
 </script>
</body>
</html>

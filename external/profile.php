<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an external user
require_external();

// Get user data for the external user
$user_id = $_SESSION['user_sessions']['external']['user_id'];
$user_name = $_SESSION['user_sessions']['external']['user_name'];

$page_title = "My Profile - CHMSU BAO";
$base_url = "..";

// Get user profile data
$profile_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $organization = trim($_POST['organization']);
    $address = trim($_POST['address']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Check if email already exists (excluding current user)
    if (!empty($email) && $email !== $user_data['email']) {
        $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($email_check_query);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email is already in use by another account.";
        }
    }
    
    // Process password change if requested
    $password_updated = false;
    if (!empty($_POST['new_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password.";
        } elseif (!password_verify($current_password, $user_data['password'])) {
            $errors[] = "Current password is incorrect.";
        }
        
        // Validate new password
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_updated = true;
        }
    }
    
    // Process profile picture upload
    $profile_pic_updated = false;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_type = $_FILES['profile_pic']['type'];
        $file_size = $_FILES['profile_pic']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($file_size > $max_size) {
            $errors[] = "Image size should not exceed 2MB.";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/profile_pics/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                // Delete old profile picture if exists
                if (!empty($user_data['profile_pic']) && file_exists('../' . $user_data['profile_pic'])) {
                    unlink('../' . $user_data['profile_pic']);
                }
                
                $profile_pic_path = 'uploads/profile_pics/' . $filename;
                $profile_pic_updated = true;
            } else {
                $errors[] = "Failed to upload profile picture. Please try again.";
            }
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        // Build the update query based on what's being updated
        $update_fields = ["name = ?", "email = ?", "phone = ?", "organization = ?", "address = ?"];
        $param_types = "sssss";
        $params = [$name, $email, $phone, $organization, $address];
        
        if ($password_updated) {
            $update_fields[] = "password = ?";
            $param_types .= "s";
            $params[] = $hashed_password;
        }
        
        if ($profile_pic_updated) {
            $update_fields[] = "profile_pic = ?";
            $param_types .= "s";
            $params[] = $profile_pic_path;
        }
        
        $update_query = "UPDATE users SET " . implode(", ", $update_fields) . ", updated_at = NOW() WHERE id = ?";
        $param_types .= "i";
        $params[] = $user_id;
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param($param_types, ...$params);
        
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['user_sessions']['external']['user_name'] = $name;
            
            $_SESSION['success'] = "Profile updated successfully.";
            
            // Refresh user data
            $stmt = $conn->prepare($profile_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
        } else {
            $_SESSION['error'] = "Failed to update profile. Please try again.";
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    // Redirect to avoid form resubmission
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include '../includes/external_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <?php include '../includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-6">My Profile</h1>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $_SESSION['success']; ?></p>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $_SESSION['error']; ?></p>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Profile Summary Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex flex-col items-center">
                            <div class="w-32 h-32 rounded-full overflow-hidden mb-4 border-4 border-blue-100">
                                <?php if (!empty($user_data['profile_pic']) && file_exists('../' . $user_data['profile_pic'])): ?>
                                    <img src="../<?php echo $user_data['profile_pic']; ?>" alt="Profile Picture" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-blue-50 text-blue-500">
                                        <i class="fas fa-user text-5xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['name']); ?></h2>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($user_data['organization']); ?></p>
                            
                            <div class="w-full border-t border-gray-200 pt-4 mt-2">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-envelope text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600"><?php echo htmlspecialchars($user_data['email']); ?></span>
                                </div>
                                
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-phone text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600"><?php echo !empty($user_data['phone']) ? htmlspecialchars($user_data['phone']) : 'Not provided'; ?></span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600"><?php echo !empty($user_data['address']) ? htmlspecialchars($user_data['address']) : 'Not provided'; ?></span>
                                </div>
                            </div>
                            
                            <div class="w-full border-t border-gray-200 pt-4 mt-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-calendar-alt text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600">Member since: <?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600">Last updated: <?php echo date('F j, Y', strtotime($user_data['updated_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Profile Form -->
                    <div class="bg-white rounded-lg shadow-md p-6 md:col-span-2">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Edit Profile</h2>
                        
                        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="organization" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                                    <input type="text" id="organization" name="organization" value="<?php echo htmlspecialchars($user_data['organization'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea id="address" name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="profile_pic" class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                                <input type="file" id="profile_pic" name="profile_pic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" accept="image/jpeg,image/png,image/gif">
                                <p class="text-xs text-gray-500 mt-1">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</p>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <h3 class="text-lg font-medium text-gray-800 mb-3">Change Password</h3>
                                <p class="text-sm text-gray-600 mb-3">Leave blank if you don't want to change your password</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    
                                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                            <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                                        </div>
                                        
                                        <div>
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" name="update_profile" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menu-button')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
        
        // Preview profile picture before upload
        document.getElementById('profile_pic').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profilePicContainer = document.querySelector('.rounded-full');
                    profilePicContainer.innerHTML = `<img src="${e.target.result}" alt="Profile Picture Preview" class="w-full h-full object-cover">`;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

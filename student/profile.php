<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "My Profile - CHMSU BAO";
$base_url = "..";

$success_message = '';
$error_message = '';

// Get current user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM user_accounts WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $organization = sanitize_input($_POST['organization']);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    
    $profile_pic_updated = false;
    $profile_pic_path = '';
    
    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profile_pics/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            // Delete old profile picture if exists
            if (!empty($user['profile_pic']) && file_exists('../' . $user['profile_pic'])) {
                unlink('../' . $user['profile_pic']);
            }
            
            $profile_pic_path = 'uploads/profile_pics/' . $filename;
            $profile_pic_updated = true;
        } else {
            $error_message = "Failed to upload profile picture. Please try again.";
        }
    }
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields.";
    } else {
        // Check if email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM user_accounts WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email already exists. Please use a different email.";
        } else {
            // Build the update query based on what's being updated
            $update_fields = ["name = ?", "email = ?", "organization = ?", "phone = ?", "address = ?"];
            $param_types = "sssss";
            $params = [$name, $email, $organization, $phone, $address];
            
            if ($profile_pic_updated) {
                $update_fields[] = "profile_pic = ?";
                $param_types .= "s";
                $params[] = $profile_pic_path;
            }
            
            $update_query = "UPDATE user_accounts SET " . implode(", ", $update_fields) . ", updated_at = NOW() WHERE id = ?";
            $param_types .= "i";
            $params[] = $user_id;
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param($param_types, ...$params);
            
            if ($stmt->execute()) {
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // For multi-session support
                if (isset($_SESSION['user_sessions']['student'])) {
                    $_SESSION['user_sessions']['student']['user_name'] = $name;
                    $_SESSION['user_sessions']['student']['user_email'] = $email;
                }
                
                $success_message = "Profile updated successfully!";
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM user_accounts WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error_message = "Error updating profile: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE user_accounts SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}

?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">My Profile</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $_SESSION['user_name']; ?></span>
                    <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500" id="menu-button">
                        <span class="sr-only">Open menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-4">
            <div class="max-w-7xl mx-auto">
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Profile Summary Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex flex-col items-center">
                            <div class="w-32 h-32 rounded-full overflow-hidden mb-4 border-4 border-blue-100">
                                <?php if (!empty($user['profile_pic']) && file_exists('../' . $user['profile_pic'])): ?>
                                    <img src="../<?php echo $user['profile_pic']; ?>" alt="Profile Picture" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-blue-50 text-blue-500">
                                        <i class="fas fa-user text-5xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($user['organization'] ?? ''); ?></p>
                            
                            <div class="w-full border-t border-gray-200 pt-4 mt-2">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-envelope text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-phone text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not provided'; ?></span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600"><?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not provided'; ?></span>
                                </div>
                            </div>
                            
                            <div class="w-full border-t border-gray-200 pt-4 mt-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-calendar-alt text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600">Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-gray-500 w-5"></i>
                                    <span class="ml-2 text-gray-600">Last updated: <?php echo date('F j, Y', strtotime($user['updated_at'])); ?></span>
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
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="organization" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                                    <input type="text" id="organization" name="organization" value="<?php echo htmlspecialchars($user['organization'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea id="address" name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="profile_pic" class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                                <input type="file" id="profile_pic" name="profile_pic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" accept="image/jpeg,image/png,image/gif">
                                <p class="text-xs text-gray-500 mt-1">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</p>
                            </div>
                            
                            <div class="flex justify-end space-x-3 mt-6">
                                <button type="submit" name="update_profile" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <i class="fas fa-save mr-2"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                    <!-- Change Password -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                            <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                            <p class="mt-1 text-sm text-gray-500">Ensure your account is using a secure password.</p>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-4">
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500" id="new_password" name="new_password" required>
                                    <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long.</p>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                    <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });

    // Full Name validation - letters only, capitalize first letter
    const nameField = document.getElementById('name');
    nameField.addEventListener('input', function(e) {
        // Remove any non-letter characters (except spaces)
        let value = e.target.value.replace(/[^a-zA-Z\s]/g, '');
        
        // Capitalize first letter of each word
        value = value.toLowerCase().replace(/\b\w/g, function(char) {
            return char.toUpperCase();
        });
        
        e.target.value = value;
    });

    // Handle paste for name field
    nameField.addEventListener('paste', function(e) {
        e.preventDefault();
        let pastedText = (e.clipboardData || window.clipboardData).getData('text');
        // Remove non-letter characters and capitalize
        pastedText = pastedText.replace(/[^a-zA-Z\s]/g, '');
        pastedText = pastedText.toLowerCase().replace(/\b\w/g, function(char) {
            return char.toUpperCase();
        });
        let start = e.target.selectionStart;
        let end = e.target.selectionEnd;
        let text = e.target.value;
        e.target.value = text.substring(0, start) + pastedText + text.substring(end);
        e.target.selectionStart = e.target.selectionEnd = start + pastedText.length;
    });

    // Organization validation - letters only, capitalize first letter
    const orgField = document.getElementById('organization');
    orgField.addEventListener('input', function(e) {
        // Remove any non-letter characters (except spaces)
        let value = e.target.value.replace(/[^a-zA-Z\s]/g, '');
        
        // Capitalize first letter of each word
        value = value.toLowerCase().replace(/\b\w/g, function(char) {
            return char.toUpperCase();
        });
        
        e.target.value = value;
    });

    // Handle paste for organization field
    orgField.addEventListener('paste', function(e) {
        e.preventDefault();
        let pastedText = (e.clipboardData || window.clipboardData).getData('text');
        // Remove non-letter characters and capitalize
        pastedText = pastedText.replace(/[^a-zA-Z\s]/g, '');
        pastedText = pastedText.toLowerCase().replace(/\b\w/g, function(char) {
            return char.toUpperCase();
        });
        let start = e.target.selectionStart;
        let end = e.target.selectionEnd;
        let text = e.target.value;
        e.target.value = text.substring(0, start) + pastedText + text.substring(end);
        e.target.selectionStart = e.target.selectionEnd = start + pastedText.length;
    });

    // Phone Number validation - numbers only, max 11 digits
    const phoneField = document.getElementById('phone');
    phoneField.addEventListener('input', function(e) {
        // Remove any non-numeric characters
        let value = e.target.value.replace(/[^0-9]/g, '');
        // Limit to 11 digits
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        e.target.value = value;
    });

    // Handle paste for phone field
    phoneField.addEventListener('paste', function(e) {
        e.preventDefault();
        let pastedText = (e.clipboardData || window.clipboardData).getData('text');
        // Remove non-numeric characters
        pastedText = pastedText.replace(/[^0-9]/g, '');
        // Limit to 11 digits
        if (pastedText.length > 11) {
            pastedText = pastedText.substring(0, 11);
        }
        let start = e.target.selectionStart;
        let end = e.target.selectionEnd;
        let text = e.target.value;
        let newValue = text.substring(0, start) + pastedText + text.substring(end);
        // Ensure total length doesn't exceed 11
        newValue = newValue.replace(/[^0-9]/g, '');
        if (newValue.length > 11) {
            newValue = newValue.substring(0, 11);
        }
        e.target.value = newValue;
        e.target.selectionStart = e.target.selectionEnd = Math.min(start + pastedText.length, 11);
    });

    // Address validation - block @, !, *, $ characters
    document.getElementById('address').addEventListener('keypress', function(e) {
        // Block @, !, *, $ characters
        if (e.key === '@' || e.key === '!' || e.key === '*' || e.key === '$') {
            e.preventDefault();
        }
    });

    // Also handle paste for address field
    document.getElementById('address').addEventListener('paste', function(e) {
        e.preventDefault();
        let pastedText = (e.clipboardData || window.clipboardData).getData('text');
        // Remove blocked characters from pasted text
        pastedText = pastedText.replace(/[@!*$]/g, '');
        // Insert the cleaned text
        let textarea = e.target;
        let start = textarea.selectionStart;
        let end = textarea.selectionEnd;
        let text = textarea.value;
        textarea.value = text.substring(0, start) + pastedText + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + pastedText.length;
    });
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>

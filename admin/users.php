<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin (secretary NOT allowed)
require_admin_only();

$page_title = "User Management - CHMSU BAO";
$base_url = "..";

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new user
        if ($_POST['action'] === 'add') {
            $name = sanitize_input($_POST['name']);
            $email = sanitize_input($_POST['email']);
            $password = $_POST['password'];
            $user_type = sanitize_input($_POST['user_type']);
            $organization = isset($_POST['organization']) ? sanitize_input($_POST['organization']) : '';
            
            // Validate inputs
            if (empty($name) || empty($email) || empty($password) || empty($user_type)) {
                $error = "All required fields must be filled out";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
                $error = "Full name must contain only letters and spaces (no numbers or symbols)";
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
                        $success = "User added successfully";
                    } else {
                        $error = "Error adding user: " . $conn->error;
                    }
                }
            }
        }
        
        // Delete user
        elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            
            // Prevent deleting yourself
            if ($id === $_SESSION['user_id']) {
                $error = "You cannot delete your own account";
            } else {
                $stmt = $conn->prepare("DELETE FROM user_accounts WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success = "User deleted successfully";
                } else {
                    $error = "Error deleting user: " . $conn->error;
                }
            }
        }
    }
}

// Pagination setup
$rows_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $rows_per_page;

// Get total count of users
$count_query = "SELECT COUNT(*) as total FROM user_accounts";
$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $rows_per_page);

// Get users with pagination
$query = "SELECT * FROM user_accounts ORDER BY user_type, name LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $rows_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
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
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Add new user button - COMMENTED OUT -->
                <!--
                <div class="mb-6">
    <button type="button" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="openAddModal()">
        <i class="fas fa-plus mr-1"></i> Add New User
    </button>
</div>
                -->
                
                <!-- Users table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">System Users</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($user = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $user['name']; ?>
                                                <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">You</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['email']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php if ($user['user_type'] === 'admin'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">BAO Admin</span>
                                                <?php elseif ($user['user_type'] === 'secretary'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">BAO Secretary</span>
                                                <?php elseif ($user['user_type'] === 'staff'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Staff</span>
                                                <?php elseif ($user['user_type'] === 'student'): ?>
                                                    <?php 
                                                    $role = $user['role'] ?? 'student';
                                                    $roleLabels = [
                                                        'student' => 'Student',
                                                        'faculty' => 'Faculty',
                                                        'staff' => 'Staff'
                                                    ];
                                                    $roleLabel = $roleLabels[$role] ?? 'Student';
                                                    ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo $roleLabel; ?></span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">External User</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['organization'] ?: '-'; ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php 
                                                if (!empty($user['created_at'])) {
                                                    $created_at = new DateTime($user['created_at']);
                                                    echo '<div>' . $created_at->format('M d, Y') . '</div>';
                                                    echo '<div class="text-xs text-gray-400">' . $created_at->format('g:i A') . '</div>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <button type="button" class="text-red-600 hover:text-red-900 delete-user-btn" 
                                                            data-user-id="<?php echo $user['id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>"
                                                            data-user-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                                            data-user-type="<?php echo htmlspecialchars($user['user_type'], ENT_QUOTES); ?>"
                                                            data-user-role="<?php echo htmlspecialchars($user['role'] ?? '', ENT_QUOTES); ?>">
                                                        Delete
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Cannot Delete</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-4 py-4 border-t border-gray-200 flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing 
                                <span class="font-medium"><?php echo $offset + 1; ?></span> 
                                to 
                                <span class="font-medium"><?php echo min($offset + $rows_per_page, $total_rows); ?></span> 
                                of 
                                <span class="font-medium"><?php echo $total_rows; ?></span> 
                                results
                            </div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <!-- Previous Button -->
                                <a href="?page=<?php echo max(1, $current_page - 1); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $current_page == 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <!-- Page Numbers -->
                                <?php 
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                if ($start_page > 1): ?>
                                    <a href="?page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" 
                                       class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?php echo $total_pages; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $total_pages; ?></a>
                                <?php endif; ?>
                                
                                <!-- Next Button -->
                                <a href="?page=<?php echo min($total_pages, $current_page + 1); ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $current_page == $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900">Delete User</h3>
            </div>
        </div>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div>
                    <p class="text-xs font-medium text-gray-500">Name:</p>
                    <p class="text-sm text-gray-900" id="delete-user-name"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Email:</p>
                    <p class="text-sm text-gray-900" id="delete-user-email"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">User Type:</p>
                    <p class="text-sm text-gray-900" id="delete-user-type"></p>
                </div>
            </div>
            <p class="text-xs text-red-600 font-semibold mt-3">
                <i class="fas fa-exclamation-circle mr-1"></i>
                Warning: This will permanently delete the user account and all associated data.
            </p>
        </div>
        
        <form method="POST" action="users.php" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-user-id">
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-trash mr-2"></i>
                    Yes, Delete User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Add New User</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeAddModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="name" name="name" required pattern="[a-zA-Z\s]+" title="Full name must contain only letters and spaces (no numbers or symbols)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
            </div>
            <div class="mb-4">
                <label for="user_type" class="block text-sm font-medium text-gray-700 mb-1">User Type</label>
                <select id="user_type" name="user_type" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50" onchange="toggleOrganizationField()">
                    <option value="admin">BAO Admin</option>
                    <option value="secretary">BAO Secretary</option>
                </select>
            </div>
            <div id="organization_field" class="mb-4 hidden">
                <label for="organization" class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
                <input type="text" id="organization" name="organization" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
            </div>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeAddModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
    Add User
</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Add modal functions
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
        // Reset form to ensure clean state
        document.getElementById('addModal').querySelector('form').reset();
        toggleOrganizationField(); // Update organization field visibility
    }
    
    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }
    
    // Toggle organization field based on user type
    function toggleOrganizationField() {
        const userType = document.getElementById('user_type').value;
        const organizationField = document.getElementById('organization_field');
        
        // Organization field is no longer needed since external option is removed
        organizationField.classList.add('hidden');
    }
    
    // Delete modal functions
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners to all delete buttons
        document.querySelectorAll('.delete-user-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                const userEmail = this.getAttribute('data-user-email');
                const userType = this.getAttribute('data-user-type');
                const userRole = this.getAttribute('data-user-role');
                
                openDeleteModal(userId, userName, userEmail, userType, userRole);
            });
        });
    });
    
    function openDeleteModal(userId, userName, userEmail, userType, userRole) {
        document.getElementById('delete-user-id').value = userId;
        document.getElementById('delete-user-name').textContent = userName;
        document.getElementById('delete-user-email').textContent = userEmail;
        
        // Format user type for display
        const userTypeMap = {
            'admin': 'BAO Admin',
            'secretary': 'BAO Secretary',
            'staff': 'Staff',
            'student': 'Student',
            'external': 'External User'
        };
        
        // Format role for display
        const roleLabels = {
            'student': 'Student',
            'faculty': 'Faculty',
            'staff': 'Staff'
        };
        
        let displayType = userTypeMap[userType] || userType;
        if (userType === 'student' && userRole && roleLabels[userRole]) {
            displayType = roleLabels[userRole];
        }
        
        document.getElementById('delete-user-type').textContent = displayType;
        
        document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    
    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.id === 'deleteModal') {
            closeDeleteModal();
        }
        if (event.target.id === 'addModal') {
            closeAddModal();
        }
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDeleteModal();
            closeAddModal();
        }
    });
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>
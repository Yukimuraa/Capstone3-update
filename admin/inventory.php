<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

$page_title = "Inventory Management - CHMSU BAO";
$base_url = "..";

// Create uploads directory if it doesn't exist
$upload_dir = "../uploads/inventory/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   if (isset($_POST['action'])) {
       // Add new item
       if ($_POST['action'] === 'add') {
           $name = sanitize_input($_POST['name']);
           $description = sanitize_input($_POST['description']);
           $price = floatval($_POST['price']);
           $quantity = intval($_POST['quantity']);
           $in_stock = isset($_POST['in_stock']) ? 1 : 0;
           
           // Handle sizes for specific clothing items
           $sizes = null;
           $sizingItems = ['BSIT OJT - Shirt', 'NSTP Shirt - CWTS', 'NSTP Shirt - LTS', 'NSTP Shirt - ROTC', 'P.E - Pants', 'P.E T-Shirt'];
           $needs_sizes = false;
           
           foreach ($sizingItems as $item) {
               if (strpos($name, $item) !== false) {
                   $needs_sizes = true;
                   break;
               }
           }
           
           if ($needs_sizes && isset($_POST['sizes']) && !empty($_POST['sizes'])) {
               $sizes = json_encode($_POST['sizes']);
           }
           
           // Handle image upload
           $image_path = null;
           if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
               $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
               if (in_array($_FILES['image']['type'], $allowed_types)) {
                   $file_name = time() . '_' . basename($_FILES['image']['name']);
                   $target_path = $upload_dir . $file_name;
                   
                   if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                       $image_path = 'uploads/inventory/' . $file_name;
                   } else {
                       $error_message = "Error uploading file.";
                   }
               } else {
                   $error_message = "Invalid file type. Only JPG, PNG and GIF are allowed.";
               }
           }
           
           $stmt = $conn->prepare("INSERT INTO inventory (name, description, price, quantity, in_stock, image_path, sizes) VALUES (?, ?, ?, ?, ?, ?, ?)");
           $stmt->bind_param("ssdisss", $name, $description, $price, $quantity, $in_stock, $image_path, $sizes);
           
           if ($stmt->execute()) {
               $success_message = "Item added successfully";
           } else {
               $error_message = "Error adding item: " . $conn->error;
           }
       }
       
       // Update item
       elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
           $id = intval($_POST['id']);
           $name = sanitize_input($_POST['name']);
           $description = sanitize_input($_POST['description']);
           $price = floatval($_POST['price']);
           $quantity = intval($_POST['quantity']);
           $in_stock = isset($_POST['in_stock']) ? 1 : 0;
           
           // Handle sizes for specific clothing items
           $sizes = null;
           $sizingItems = ['BSIT OJT - Shirt', 'NSTP Shirt - CWTS', 'NSTP Shirt - LTS', 'NSTP Shirt - ROTC', 'P.E - Pants', 'P.E T-Shirt'];
           $needs_sizes = false;
           
           foreach ($sizingItems as $item) {
               if (strpos($name, $item) !== false) {
                   $needs_sizes = true;
                   break;
               }
           }
           
           if ($needs_sizes && isset($_POST['sizes']) && !empty($_POST['sizes'])) {
               $sizes = json_encode($_POST['sizes']);
           }
           
           // Get current image path
           $stmt = $conn->prepare("SELECT image_path FROM inventory WHERE id = ?");
           $stmt->bind_param("i", $id);
           $stmt->execute();
           $result = $stmt->get_result();
           $item = $result->fetch_assoc();
           $current_image = $item['image_path'];
           
           // Handle image upload
           $image_path = $current_image;
           if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
               $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
               if (in_array($_FILES['image']['type'], $allowed_types)) {
                   $file_name = time() . '_' . basename($_FILES['image']['name']);
                   $target_path = $upload_dir . $file_name;
                   
                   if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                       $image_path = 'uploads/inventory/' . $file_name;
                       
                       // Delete old image if exists
                       if ($current_image && file_exists("../" . $current_image)) {
                           unlink("../" . $current_image);
                       }
                   } else {
                       $error_message = "Error uploading file.";
                   }
               } else {
                   $error_message = "Invalid file type. Only JPG, PNG and GIF are allowed.";
               }
           }
           
           // Handle image deletion
           if (isset($_POST['delete_image']) && $_POST['delete_image'] == 1) {
               if ($current_image && file_exists("../" . $current_image)) {
                   unlink("../" . $current_image);
               }
               $image_path = null;
           }
           
           $stmt = $conn->prepare("UPDATE inventory SET name = ?, description = ?, price = ?, quantity = ?, in_stock = ?, image_path = ?, sizes = ? WHERE id = ?");
           $stmt->bind_param("ssdisssi", $name, $description, $price, $quantity, $in_stock, $image_path, $sizes, $id);
           
           if ($stmt->execute()) {
               $success_message = "Item updated successfully";
           } else {
               $error_message = "Error updating item: " . $conn->error;
           }
       }
       
       // Delete item
       elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
           $id = intval($_POST['id']);
           
           // Get image path before deleting
           $stmt = $conn->prepare("SELECT image_path FROM inventory WHERE id = ?");
           $stmt->bind_param("i", $id);
           $stmt->execute();
           $result = $stmt->get_result();
           $item = $result->fetch_assoc();
           
           $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
           $stmt->bind_param("i", $id);
           
           if ($stmt->execute()) {
               // Delete image file if exists
               if ($item['image_path'] && file_exists("../" . $item['image_path'])) {
                   unlink("../" . $item['image_path']);
               }
               $success_message = "Item deleted successfully";
           } else {
               $error_message = "Error deleting item: " . $conn->error;
           }
       }
   }
}

// Get inventory items
$query = "SELECT * FROM inventory ORDER BY name";
$result = $conn->query($query);

// Get low stock items count (less than 20)
$low_stock_query = "SELECT COUNT(*) as low_stock_count FROM inventory WHERE quantity < 20";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_count = 0; // Default value
if ($low_stock_result && $low_stock_result->num_rows > 0) {
    $low_stock_count = $low_stock_result->fetch_assoc()['low_stock_count'];
}

// Get low stock items for notification
$low_stock_items_query = "SELECT * FROM inventory WHERE quantity < 20 ORDER BY quantity ASC";
$low_stock_items = $conn->query($low_stock_items_query);
if (!$low_stock_items) {
    $low_stock_items = []; // Set default empty array if query fails
}

// Get total inventory items count
$total_items_query = "SELECT COUNT(*) as total_inventory FROM inventory";
$total_items = $conn->query($total_items_query)->fetch_assoc();

// Get purchase statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(quantity) as total_items,
    SUM(total_price) as total_revenue
FROM orders 
WHERE status = 'completed'";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
   <?php include '../includes/admin_sidebar.php'; ?>
   
   <div class="flex-1 flex flex-col overflow-hidden">
       <!-- Top header -->
       <header class="bg-white shadow-sm z-10">
           <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
               <h1 class="text-2xl font-semibold text-gray-900">Inventory Management</h1>
               <div class="flex items-center space-x-4">
                   <!-- Notification Bell -->
                   <div class="relative">
                       <button id="notification-bell" class="relative p-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                           <i class="fas fa-bell text-xl"></i>
                           <?php if ($low_stock_count > 0): ?>
                           <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                               <?php echo $low_stock_count; ?>
                           </span>
                           <?php endif; ?>
                       </button>
                       <!-- Notification Dropdown -->
                       <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50">
                           <div class="p-4 border-b">
                               <h3 class="text-lg font-semibold text-gray-900">Low Stock Notifications</h3>
                           </div>
                           <div class="max-h-96 overflow-y-auto">
                               <?php if ($low_stock_count > 0): ?>
                                   <?php while ($item = $low_stock_items->fetch_assoc()): ?>
                                   <div class="p-4 border-b hover:bg-gray-50">
                                       <div class="flex items-center justify-between">
                                           <div>
                                               <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></p>
                                               <p class="text-sm text-red-600">Only <?php echo $item['quantity']; ?> items left</p>
                                           </div>
                                       </div>
                                   </div>
                                   <?php endwhile; ?>
                               <?php else: ?>
                                   <div class="p-4 text-center text-gray-500">
                                       No low stock items
                                   </div>
                               <?php endif; ?>
                           </div>
                       </div>
                   </div>
                   <span class="text-gray-700"><?php echo $_SESSION['user_name']; ?></span>
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
               <?php if (isset($success_message)): ?>
                   <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                       <p><?php echo $success_message; ?></p>
                   </div>
               <?php endif; ?>
               
               <?php if (isset($error_message)): ?>
                   <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                       <p><?php echo $error_message; ?></p>
                   </div>
               <?php endif; ?>
               
               <div class="flex items-center justify-between mb-6">
                   <div>
                       <h2 class="text-xl font-bold tracking-tight text-gray-900">Manage Inventory</h2>
                       <p class="text-gray-500">Add, edit, or remove items from the inventory</p>
                   </div>
                   <div class="flex items-center gap-4">
                       <!-- Purchase Statistics -->
                       <div class="bg-white rounded-lg shadow p-4">
                           <div class="flex items-center gap-6">
                               <div class="text-center">
                                   <h3 class="text-sm font-medium text-gray-500">Total Inventory Items</h3>
                                   <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($total_items['total_inventory']); ?></p>
                               </div>
                               <div class="text-center">
                                   <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
                                   <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total_orders']); ?></p>
                               </div>
                               <div class="text-center">
                                   <h3 class="text-sm font-medium text-gray-500">Total Items Sold</h3>
                                   <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total_items']); ?></p>
                               </div>
                               <div class="text-center">
                                   <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
                                   <div class="relative">
                                       <select id="revenue-period" class="text-2xl font-semibold text-gray-900 bg-transparent border-none focus:ring-0 cursor-pointer">
                                           <option value="all">₱<?php echo number_format($stats['total_revenue'], 2); ?></option>
                                           <option value="monthly">Monthly Revenue</option>
                                           <option value="yearly">Yearly Revenue</option>
                                       </select>
                                   </div>
                               </div>
                           </div>
                       </div>
                       <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" onclick="openAddModal()">
                           <i class="fas fa-plus mr-2"></i> Add New Item
                       </button>
                   </div>
               </div>
               
               <!-- Inventory table -->
               <div class="bg-white rounded-lg shadow">
                   <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                       <h3 class="text-lg font-medium text-gray-900">Inventory Items</h3>
                   </div>
                   <div class="overflow-x-auto">
                       <table class="min-w-full divide-y divide-gray-200">
                           <thead class="bg-gray-50">
                               <tr>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                   <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                   <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                   <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                               </tr>
                           </thead>
                           <tbody class="bg-white divide-y divide-gray-200">
                               <?php if ($result->num_rows > 0): ?>
                                   <?php while ($item = $result->fetch_assoc()): ?>
                                       <tr class="<?php echo $item['quantity'] < 20 ? 'bg-red-50' : ''; ?>">
                                           <td class="px-6 py-4 whitespace-nowrap">
                                               <?php if ($item['image_path']): ?>
                                                   <img src="<?php echo $base_url . '/' . $item['image_path']; ?>" alt="<?php echo $item['name']; ?>" class="h-16 w-16 object-cover rounded-md">
                                               <?php else: ?>
                                                   <div class="h-16 w-16 bg-gray-200 rounded-md flex items-center justify-center">
                                                       <i class="fas fa-image text-gray-400 text-xl"></i>
                                                   </div>
                                               <?php endif; ?>
                                           </td>
                                           <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $item['name']; ?></td>
                                           <td class="px-6 py-4 text-sm text-gray-500"><?php echo $item['description']; ?></td>
                                           <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">₱<?php echo number_format($item['price'], 2); ?></td>
                                           <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"><?php echo $item['quantity']; ?></td>
                                           <td class="px-6 py-4 whitespace-nowrap">
                                               <?php if ($item['in_stock']): ?>
                                                   <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">In Stock</span>
                                               <?php else: ?>
                                                   <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Out of Stock</span>
                                               <?php endif; ?>
                                           </td>
                                           <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                               <button type="button" class="text-emerald-600 hover:text-emerald-900 mr-3" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)'>Edit</button>
                                               <form method="POST" action="inventory.php" class="inline">
                                                   <input type="hidden" name="action" value="delete">
                                                   <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                   <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
                                               </form>
                                           </td>
                                       </tr>
                                   <?php endwhile; ?>
                               <?php else: ?>
                                   <tr>
                                       <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No inventory items found</td>
                                   </tr>
                               <?php endif; ?>
                           </tbody>
                       </table>
                   </div>
               </div>
           </div>
       </main>
   </div>
</div>

<!-- Add Item Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
   <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
       <div class="flex justify-between items-center mb-4">
           <h3 class="text-lg font-medium text-gray-900">Add New Item</h3>
           <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeAddModal()">
               <i class="fas fa-times"></i>
           </button>
       </div>
       <form method="POST" action="inventory.php" enctype="multipart/form-data">
           <input type="hidden" name="action" value="add">
           <div class="mb-4">
               <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
               <input type="text" id="name" name="name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
           </div>
           <div class="mb-4">
               <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
               <textarea id="description" name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50"></textarea>
           </div>
           <div id="sizes-container" class="mb-4 hidden">
               <label class="block text-sm font-medium text-gray-700 mb-1">Available Sizes</label>
               <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                   <div class="flex items-center">
                       <input type="checkbox" id="size_xs" name="sizes[]" value="XS" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="size_xs" class="ml-2 block text-sm text-gray-700">Extra Small (XS)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="size_s" name="sizes[]" value="S" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="size_s" class="ml-2 block text-sm text-gray-700">Small (S)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="size_m" name="sizes[]" value="M" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="size_m" class="ml-2 block text-sm text-gray-700">Medium (M)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="size_l" name="sizes[]" value="L" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="size_l" class="ml-2 block text-sm text-gray-700">Large (L)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="size_xl" name="sizes[]" value="XL" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="size_xl" class="ml-2 block text-sm text-gray-700">Extra Large (XL)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="size_2xl" name="sizes[]" value="2XL" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="size_2xl" class="ml-2 block text-sm text-gray-700">2XL</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="size_3xl" name="sizes[]" value="3XL" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="size_3xl" class="ml-2 block text-sm text-gray-700">3XL</label>
                   </div>
               </div>
           </div>
           <div class="grid grid-cols-2 gap-4 mb-4">
               <div>
                   <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (₱)</label>
                   <input type="number" id="price" name="price" step="0.01" min="0" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
               </div>
               <div>
                   <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                   <input type="number" id="quantity" name="quantity" min="0" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
               </div>
           </div>
           <div class="mb-4">
               <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Item Image</label>
               <div class="flex items-center justify-center w-full">
                   <label for="image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                       <div class="flex flex-col items-center justify-center pt-5 pb-6">
                           <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                           <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                           <p class="text-xs text-gray-500">PNG, JPG or GIF (Max. 2MB)</p>
                       </div>
                       <input id="image" name="image" type="file" class="hidden" accept="image/png, image/jpeg, image/gif" />
                   </label>
               </div>
               <div id="image-preview-container" class="mt-2 hidden">
                   <div class="relative w-full h-32">
                       <img id="image-preview" class="w-full h-full object-contain" src="#" alt="Image preview" />
                       <button type="button" id="remove-image" class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1 shadow-md">
                           <i class="fas fa-times"></i>
                       </button>
                   </div>
               </div>
           </div>
           <div class="mb-6">
               <div class="flex items-center">
                   <input type="checkbox" id="in_stock" name="in_stock" checked class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                   <label for="in_stock" class="ml-2 block text-sm text-gray-700">In Stock</label>
               </div>
           </div>
           <div class="flex justify-end">
               <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeAddModal()">
                   Cancel
               </button>
               <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                   Add Item
               </button>
           </div>
       </form>
   </div>
</div>

<!-- Edit Item Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
   <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
       <div class="flex justify-between items-center mb-4">
           <h3 class="text-lg font-medium text-gray-900">Edit Item</h3>
           <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeEditModal()">
               <i class="fas fa-times"></i>
           </button>
       </div>
       <form method="POST" action="inventory.php" enctype="multipart/form-data">
           <input type="hidden" name="action" value="update">
           <input type="hidden" id="edit_id" name="id">
           <div class="mb-4">
               <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
               <input type="text" id="edit_name" name="name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
           </div>
           <div class="mb-4">
               <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
               <textarea id="edit_description" name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50"></textarea>
           </div>
           <div id="edit_sizes_container" class="mb-4 hidden">
               <label class="block text-sm font-medium text-gray-700 mb-1">Available Sizes</label>
               <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                   <div class="flex items-center">
                       <input type="checkbox" id="edit_size_xs" name="sizes[]" value="XS" class="size-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="edit_size_xs" class="ml-2 block text-sm text-gray-700">Extra Small (XS)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="edit_size_s" name="sizes[]" value="S" class="size-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="edit_size_s" class="ml-2 block text-sm text-gray-700">Small (S)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="edit_size_m" name="sizes[]" value="M" class="size-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="edit_size_m" class="ml-2 block text-sm text-gray-700">Medium (M)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="edit_size_l" name="sizes[]" value="L" class="size-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="edit_size_l" class="ml-2 block text-sm text-gray-700">Large (L)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="edit_size_xl" name="sizes[]" value="XL" class="size-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="edit_size_xl" class="ml-2 block text-sm text-gray-700">Extra Large (XL)</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="edit_size_2xl" name="sizes[]" value="2XL" class="size-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="edit_size_2xl" class="ml-2 block text-sm text-gray-700">2XL</label>
                   </div>
                   <div class="flex items-center">
                       <input type="checkbox" id="edit_size_3xl" name="sizes[]" value="3XL" class="size-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                       <label for="edit_size_3xl" class="ml-2 block text-sm text-gray-700">3XL</label>
                   </div>
               </div>
           </div>
           <div class="grid grid-cols-2 gap-4 mb-4">
               <div>
                   <label for="edit_price" class="block text-sm font-medium text-gray-700 mb-1">Price (₱)</label>
                   <input type="number" id="edit_price" name="price" step="0.01" min="0" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
               </div>
               <div>
                   <label for="edit_quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                   <input type="number" id="edit_quantity" name="quantity" min="0" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
               </div>
           </div>
           <div class="mb-4">
               <label for="edit_image" class="block text-sm font-medium text-gray-700 mb-1">Item Image</label>
               <div id="current-image-container" class="mb-2 hidden">
                   <div class="relative w-full h-32">
                       <img id="current-image" class="w-full h-full object-contain border rounded-md" src="#" alt="Current image" />
                       <div class="mt-1">
                           <label class="inline-flex items-center">
                               <input type="checkbox" id="delete_image" name="delete_image" value="1" class="rounded border-gray-300 text-red-600">
                               <span class="ml-2 text-sm text-red-600">Delete current image</span>
                           </label>
                       </div>
                   </div>
               </div>
               <div class="flex items-center justify-center w-full">
                   <label for="edit_image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                       <div class="flex flex-col items-center justify-center pt-5 pb-6">
                           <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                           <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                           <p class="text-xs text-gray-500">PNG, JPG or GIF (Max. 2MB)</p>
                       </div>
                       <input id="edit_image" name="image" type="file" class="hidden" accept="image/png, image/jpeg, image/gif" />
                   </label>
               </div>
               <div id="edit-image-preview-container" class="mt-2 hidden">
                   <div class="relative w-full h-32">
                       <img id="edit-image-preview" class="w-full h-full object-contain" src="#" alt="Image preview" />
                       <button type="button" id="edit-remove-image" class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1 shadow-md">
                           <i class="fas fa-times"></i>
                       </button>
                   </div>
               </div>
           </div>
           <div class="mb-6">
               <div class="flex items-center">
                   <input type="checkbox" id="edit_in_stock" name="in_stock" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                   <label for="edit_in_stock" class="ml-2 block text-sm text-gray-700">In Stock</label>
               </div>
           </div>
           <div class="flex justify-end">
               <button type="button" class="bg-blue-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-blue-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeEditModal()">
                   Cancel
               </button>
               <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                   Update Item
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
   }
   
   function closeAddModal() {
       document.getElementById('addModal').classList.add('hidden');
       document.getElementById('image-preview-container').classList.add('hidden');
       document.getElementById('image').value = '';
   }
   
   // Edit modal functions
   function openEditModal(item) {
       document.getElementById('edit_id').value = item.id;
       document.getElementById('edit_name').value = item.name;
       document.getElementById('edit_description').value = item.description;
       document.getElementById('edit_price').value = item.price;
       document.getElementById('edit_quantity').value = item.quantity;
       document.getElementById('edit_in_stock').checked = item.in_stock == 1;
       
       // Check if item needs sizes
       const needsSizes = sizingItems.some(sizingItem => item.name.includes(sizingItem));
       const sizesContainer = document.getElementById('edit_sizes_container');
       
       if (needsSizes) {
           sizesContainer.classList.remove('hidden');
           
           // If we have sizes stored, check the appropriate boxes
           if (item.sizes) {
               try {
                   const sizeArray = JSON.parse(item.sizes);
                   document.querySelectorAll('.size-checkbox').forEach(checkbox => {
                       checkbox.checked = sizeArray.includes(checkbox.value);
                   });
               } catch (e) {
                   console.error('Error parsing sizes JSON:', e);
               }
           }
       } else {
           sizesContainer.classList.add('hidden');
       }
       
       // Handle existing image display
       const currentImageContainer = document.getElementById('current-image-container');
       if (item.image_path) {
           currentImageContainer.classList.remove('hidden');
           document.getElementById('current-image').src = '../' + item.image_path;
       } else {
           currentImageContainer.classList.add('hidden');
       }
       
       // Clear edit image preview
       document.getElementById('edit-image-preview-container').classList.add('hidden');
       document.getElementById('edit_image').value = '';
       document.getElementById('delete_image').checked = false;
       
       document.getElementById('editModal').classList.remove('hidden');
   }
   
   function closeEditModal() {
       document.getElementById('editModal').classList.add('hidden');
   }
   
   // Image preview for add form
   document.getElementById('image').addEventListener('change', function(e) {
       const file = e.target.files[0];
       if (file) {
           const reader = new FileReader();
           reader.onload = function(e) {
               const previewContainer = document.getElementById('image-preview-container');
               const preview = document.getElementById('image-preview');
               preview.src = e.target.result;
               previewContainer.classList.remove('hidden');
           }
           reader.readAsDataURL(file);
       }
   });
   
   // Remove image preview for add form
   document.getElementById('remove-image').addEventListener('click', function() {
       document.getElementById('image').value = '';
       document.getElementById('image-preview-container').classList.add('hidden');
   });
   
   // Image preview for edit form
   document.getElementById('edit_image').addEventListener('change', function(e) {
       const file = e.target.files[0];
       if (file) {
           const reader = new FileReader();
           reader.onload = function(e) {
               const previewContainer = document.getElementById('edit-image-preview-container');
               const preview = document.getElementById('edit-image-preview');
               preview.src = e.target.result;
               previewContainer.classList.remove('hidden');
               
               // Uncheck delete image checkbox if it was checked
               document.getElementById('delete_image').checked = false;
           }
           reader.readAsDataURL(file);
       }
   });
   
   // Remove image preview for edit form
   document.getElementById('edit-remove-image').addEventListener('click', function() {
       document.getElementById('edit_image').value = '';
       document.getElementById('edit-image-preview-container').classList.add('hidden');
   });
   
   // Handle delete image checkbox
   document.getElementById('delete_image').addEventListener('change', function() {
       if (this.checked) {
           // If delete is checked, clear any new image upload
           document.getElementById('edit_image').value = '';
           document.getElementById('edit-image-preview-container').classList.add('hidden');
       }
   });

   document.getElementById('revenue-period').addEventListener('change', function() {
       const period = this.value;
       if (period === 'monthly' || period === 'yearly') {
           // Make an AJAX call to get the revenue data
           fetch('get_revenue.php?period=' + period)
               .then(response => response.json())
               .then(data => {
                   if (period === 'monthly') {
                       alert('Monthly Revenue: ₱' + data.revenue.toFixed(2));
                   } else {
                       alert('Yearly Revenue: ₱' + data.revenue.toFixed(2));
                   }
                   // Reset the dropdown
                   this.value = 'all';
               })
               .catch(error => {
                   console.error('Error:', error);
                   alert('Error fetching revenue data');
                   this.value = 'all';
               });
       }
   });

   document.addEventListener('DOMContentLoaded', function() {
       const notificationBell = document.getElementById('notification-bell');
       const notificationDropdown = document.getElementById('notification-dropdown');

       notificationBell.addEventListener('click', function() {
           notificationDropdown.classList.toggle('hidden');
       });

       // Close dropdown when clicking outside
       document.addEventListener('click', function(event) {
           if (!notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
               notificationDropdown.classList.add('hidden');
           }
       });
   });

   // Add the JavaScript for size options display
   const sizingItems = [
       'BSIT OJT - Shirt', 
       'NSTP Shirt - CWTS', 
       'NSTP Shirt - LTS', 
       'NSTP Shirt - ROTC',
       'P.E - Pants',
       'P.E T-Shirt'
   ];
   
   // For the add item form
   document.getElementById('name').addEventListener('input', function() {
       const itemName = this.value.trim();
       const sizesContainer = document.getElementById('sizes-container');
       
       // Check if current item name is in the sizing items list
       const needsSizes = sizingItems.some(item => itemName.includes(item));
       
       if (needsSizes) {
           sizesContainer.classList.remove('hidden');
       } else {
           sizesContainer.classList.add('hidden');
       }
   });
</script>

<?php include '../includes/footer.php'; ?>

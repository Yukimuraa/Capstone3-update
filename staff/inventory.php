<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is staff
require_staff();

// Get user data for the staff
$user_id = $_SESSION['user_sessions']['staff']['user_id'];
$user_name = $_SESSION['user_sessions']['staff']['user_name'];

$page_title = "Inventory Management - CHMSU BAO";
$base_url = "..";

// Check if viewing as shopper or manager
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'manage';

// Handle inventory updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        $item_id = $_POST['item_id'];
        $quantity = $_POST['quantity'];
        $notes = $_POST['notes'];
        
        $update_query = "UPDATE inventory SET quantity = ?, last_updated = NOW(), notes = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("isi", $quantity, $notes, $item_id);
        
        if ($stmt->execute()) {
            $success_message = "Inventory updated successfully";
        } else {
            $error_message = "Failed to update inventory";
        }
    }
}

// Get all inventory items
$inventory_query = "SELECT * FROM inventory ORDER BY name ASC";
$inventory = $conn->query($inventory_query);

// Get low stock items
$low_stock_query = "SELECT * FROM inventory WHERE quantity <= 5 ORDER BY quantity ASC";
$low_stock = $conn->query($low_stock_query);
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/staff_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900"><?php echo $view_mode === 'shop' ? 'Order Items' : 'Inventory Management'; ?></h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $user_name; ?></span>
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
                <!-- View Mode Toggle -->
                <div class="mb-6 flex justify-between items-center">
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <a href="inventory.php?view=manage" class="px-4 py-2 text-sm font-medium <?php echo $view_mode === 'manage' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border border-gray-300 rounded-l-lg">
                            <i class="fas fa-cog mr-2"></i> Manage Inventory
                        </a>
                        <a href="inventory.php?view=shop" class="px-4 py-2 text-sm font-medium <?php echo $view_mode === 'shop' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border border-gray-300 rounded-r-lg">
                            <i class="fas fa-shopping-cart mr-2"></i> Order Items
                        </a>
                    </div>
                    
                    <?php if ($view_mode === 'shop'): ?>
                        <a href="cart.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                            <i class="fas fa-shopping-cart mr-2"></i> 
                            View Cart 
                            <span id="cart-badge" class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-emerald-600 bg-white rounded-full">
                                <?php
                                $cart_count_query = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
                                $cart_count_query->bind_param("i", $user_id);
                                $cart_count_query->execute();
                                $cart_count = $cart_count_query->get_result()->fetch_assoc()['count'];
                                echo $cart_count;
                                ?>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Low Stock Alert -->
                <?php if ($low_stock->num_rows > 0): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Low Stock Alert</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>The following items are running low on stock:</p>
                                    <ul class="list-disc list-inside mt-1">
                                        <?php while ($item = $low_stock->fetch_assoc()): ?>
                                            <li><?php echo $item['name']; ?> (<?php echo $item['quantity']; ?> remaining)</li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($view_mode === 'shop'): ?>
                    <!-- Shopping View -->
                    <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                        <?php 
                        $inventory->data_seek(0); // Reset pointer
                        while ($item = $inventory->fetch_assoc()): 
                        ?>
                            <div class="bg-white rounded-lg shadow overflow-hidden <?php echo $item['in_stock'] ? '' : 'opacity-70'; ?>">
                                <div class="p-4">
                                    <div class="h-48 w-full bg-gray-100 rounded-md flex items-center justify-center mb-4 overflow-hidden">
                                        <?php if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])): ?>
                                            <img src="<?php echo '../' . $item['image_path']; ?>" alt="<?php echo $item['name']; ?>" class="h-full w-full object-contain">
                                        <?php else: ?>
                                            <i class="fas fa-box text-gray-400 text-5xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo $item['name']; ?></h3>
                                    <p class="text-sm text-gray-500 mb-4"><?php echo substr($item['description'], 0, 100); ?></p>
                                    <div class="flex justify-between items-center">
                                        <p class="font-medium text-lg">₱<?php echo number_format($item['price'], 2); ?></p>
                                        <?php if ($item['in_stock']): ?>
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                                In Stock
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                                Out of Stock
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="px-4 py-3 bg-gray-50 text-center">
                                    <button onclick="openQuickAddModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                            class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 <?php echo $item['in_stock'] ? '' : 'opacity-50 cursor-not-allowed'; ?>"
                                            <?php echo $item['in_stock'] ? '' : 'disabled'; ?>>
                                        <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <!-- Management View -->
                
                <!-- Inventory Table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Inventory Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($item = $inventory->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $item['name']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $item['quantity']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo format_date($item['last_updated']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($item['quantity'] <= 5): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Low Stock
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    In Stock
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="openUpdateModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                                    class="text-emerald-600 hover:text-emerald-900">
                                                Update
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Quick Add to Cart Modal (for shopping view) -->
<div id="quickAddModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Add to Cart</h3>
            <button onclick="closeQuickAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="quick-add-form">
            <input type="hidden" id="modal-item-id" name="item_id">
            
            <div class="mb-4">
                <h4 class="font-medium text-gray-900" id="modal-item-name"></h4>
                <p class="text-sm text-gray-600" id="modal-item-price"></p>
            </div>
            
            <div id="modal-size-section" class="mb-4 hidden">
                <label for="modal-size" class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                <select id="modal-size" name="size" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Select Size</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="modal-quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                <input type="number" id="modal-quantity" name="quantity" value="1" min="1" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeQuickAddModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Update Inventory Modal -->
<div id="updateModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="inventory.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="item_id" id="updateItemId">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="updateItemName">
                                Update Inventory
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <input type="number" name="quantity" id="updateQuantity" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                    <textarea name="notes" id="updateNotes" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update
                    </button>
                    <button type="button" onclick="closeUpdateModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notification Toast Modal -->
<div id="notificationToast" class="fixed top-4 right-4 z-50 transform translate-x-full transition-transform duration-300 ease-in-out">
    <div class="bg-white rounded-lg shadow-lg border-l-4 p-4 min-w-[300px] max-w-md">
        <div class="flex items-start">
            <div id="notificationIcon" class="flex-shrink-0 mr-3">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div class="flex-1">
                <p id="notificationMessage" class="text-sm font-medium text-gray-900"></p>
            </div>
            <button onclick="hideNotification()" class="ml-3 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Update modal functions
    function openUpdateModal(item) {
        document.getElementById('updateModal').classList.remove('hidden');
        document.getElementById('updateItemId').value = item.id;
        document.getElementById('updateItemName').textContent = `Update ${item.name}`;
        document.getElementById('updateQuantity').value = item.quantity;
        document.getElementById('updateNotes').value = item.notes || '';
    }
    
    function closeUpdateModal() {
        document.getElementById('updateModal').classList.add('hidden');
    }
    
    // Quick Add Modal Functions (for shopping view)
    let currentItem = null;
    
    function openQuickAddModal(item) {
        currentItem = item;
        document.getElementById('modal-item-id').value = item.id;
        document.getElementById('modal-item-name').textContent = item.name;
        document.getElementById('modal-item-price').textContent = '₱' + parseFloat(item.price).toFixed(2);
        document.getElementById('modal-quantity').max = item.quantity;
        
        // Check if item needs size selection
        const clothingKeywords = ['shirt', 't-shirt', 'tshirt', 'pants', 'shorts', 'jacket', 'hoodie', 'polo', 'jersey', 'uniform'];
        let needsSize = false;
        
        for (let keyword of clothingKeywords) {
            if (item.name.toLowerCase().includes(keyword)) {
                needsSize = true;
                break;
            }
        }
        
        const sizeSection = document.getElementById('modal-size-section');
        const sizeSelect = document.getElementById('modal-size');
        
        if (needsSize) {
            sizeSection.classList.remove('hidden');
            sizeSelect.required = true;
            sizeSelect.innerHTML = '<option value="">Select Size</option>';
            
            // Parse size_quantities for later use
            let sizeQuantities = {};
            if (item.size_quantities) {
                try {
                    sizeQuantities = JSON.parse(item.size_quantities);
                } catch (e) {
                    console.error('Error parsing size_quantities:', e);
                }
            }
            
            // Add sizes - only show sizes with available stock
            if (item.sizes) {
                try {
                    const sizes = JSON.parse(item.sizes);
                    
                    // Only add sizes that have stock > 0
                    sizes.forEach(size => {
                        const stock = sizeQuantities[size] || 0;
                        if (stock > 0) {
                        const option = document.createElement('option');
                        option.value = size;
                            option.textContent = size + ' (Stock: ' + stock + ')';
                            option.dataset.stock = stock;
                        sizeSelect.appendChild(option);
                        }
                    });
                } catch (e) {
                    console.error('Error parsing sizes:', e);
                    // If parsing fails, don't show any sizes
                }
            }
            
            // Update quantity max when size is selected
            // Use onchange to avoid duplicate listeners
            sizeSelect.onchange = function() {
                const quantityInput = document.getElementById('modal-quantity');
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.dataset.stock) {
                    quantityInput.max = selectedOption.dataset.stock;
                    quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, parseInt(selectedOption.dataset.stock));
                } else {
                    quantityInput.max = item.quantity;
                }
            };
        } else {
            sizeSection.classList.add('hidden');
            sizeSelect.required = false;
        }
        
        document.getElementById('quickAddModal').classList.remove('hidden');
    }
    
    function closeQuickAddModal() {
        document.getElementById('quickAddModal').classList.add('hidden');
        document.getElementById('quick-add-form').reset();
    }
    
    // Notification Toast Functions
    function showNotification(message, type = 'success') {
        const toast = document.getElementById('notificationToast');
        const icon = document.getElementById('notificationIcon');
        const messageEl = document.getElementById('notificationMessage');
        
        // Set message
        messageEl.textContent = message;
        
        // Set icon and color based on type
        if (type === 'success') {
            icon.innerHTML = '<i class="fas fa-check-circle text-2xl text-emerald-500"></i>';
            toast.querySelector('.border-l-4').classList.remove('border-red-500', 'border-yellow-500');
            toast.querySelector('.border-l-4').classList.add('border-emerald-500');
        } else if (type === 'error') {
            icon.innerHTML = '<i class="fas fa-exclamation-circle text-2xl text-red-500"></i>';
            toast.querySelector('.border-l-4').classList.remove('border-emerald-500', 'border-yellow-500');
            toast.querySelector('.border-l-4').classList.add('border-red-500');
        } else {
            icon.innerHTML = '<i class="fas fa-info-circle text-2xl text-yellow-500"></i>';
            toast.querySelector('.border-l-4').classList.remove('border-emerald-500', 'border-red-500');
            toast.querySelector('.border-l-4').classList.add('border-yellow-500');
        }
        
        // Show notification
        toast.classList.remove('translate-x-full');
        toast.classList.add('translate-x-0');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            hideNotification();
        }, 3000);
    }
    
    function hideNotification() {
        const toast = document.getElementById('notificationToast');
        toast.classList.remove('translate-x-0');
        toast.classList.add('translate-x-full');
    }
    
    // Handle quick add form submission
    const quickAddForm = document.getElementById('quick-add-form');
    if (quickAddForm) {
        quickAddForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart badge
                    const cartBadge = document.getElementById('cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    }
                    
                    // Show success notification
                    showNotification(data.message, 'success');
                    closeQuickAddModal();
                } else {
                    // Show error notification
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while adding to cart', 'error');
            });
        });
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQuickAddModal();
        }
    });
    
    // Close modal on outside click
    const quickAddModal = document.getElementById('quickAddModal');
    if (quickAddModal) {
        quickAddModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuickAddModal();
            }
        });
    }
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html> 
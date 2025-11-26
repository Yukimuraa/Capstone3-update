<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "Order Item - CHMSU BAO";
$base_url = "..";

$error = '';
$success = '';
$order_id = '';

// Check if item ID is provided
if (!isset($_GET['id'])) {
  header("Location: inventory.php");
  exit();
}

$item_id = intval($_GET['id']);

// Get item details
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: inventory.php");
  exit();
}

$item = $result->fetch_assoc();

// Check if item is in stock
if (!$item['in_stock']) {
  header("Location: inventory.php");
  exit();
}

// Get user profile picture
$user_stmt = $conn->prepare("SELECT profile_pic FROM user_accounts WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$profile_pic = $user_data['profile_pic'] ?? '';

// Get pre-filled data from URL parameters (from quick order)
$prefilled_quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
$prefilled_size = isset($_GET['size']) ? sanitize_input($_GET['size']) : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $quantity = intval($_POST['quantity']);
  $size = isset($_POST['size']) ? sanitize_input($_POST['size']) : '';
  
  // Validate quantity - check size-specific stock if size is provided
  $valid_quantity = true;
  $max_quantity = $item['quantity'];
  
  if (!empty($size) && !empty($item['size_quantities'])) {
      $size_quantities = json_decode($item['size_quantities'], true);
      if (is_array($size_quantities) && isset($size_quantities[$size])) {
          $max_quantity = $size_quantities[$size];
  } else {
          $error = "Size " . $size . " is not available for this item.";
          $valid_quantity = false;
      }
  }
  
  if ($valid_quantity && ($quantity <= 0 || $quantity > $max_quantity)) {
      $error = "Invalid quantity. Please select a quantity between 1 and " . $max_quantity;
  } else if ($valid_quantity) {
      // Start transaction to ensure all operations succeed or fail together
      $conn->begin_transaction();
      
      try {
          // Calculate total price
          $total_price = $quantity * $item['price'];
          
          // Generate unique order ID with retry mechanism
          $order_id = '';
          $max_attempts = 10;
          for ($i = 0; $i < $max_attempts; $i++) {
              // Use timestamp + larger random number for uniqueness
              $order_id = 'ORD-' . date('Ymd') . '-' . date('His') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
              
              // Check if this order_id already exists
              $check_stmt = $conn->prepare("SELECT id FROM orders WHERE order_id = ?");
              $check_stmt->bind_param("s", $order_id);
              $check_stmt->execute();
              $check_result = $check_stmt->get_result();
              
              if ($check_result->num_rows == 0) {
                  // Unique ID found, break the loop
                  break;
              }
              
              // If we're on the last attempt and still have duplicates, add microseconds
              if ($i == $max_attempts - 1) {
                  $order_id = 'ORD-' . date('Ymd') . '-' . date('His') . '-' . substr(microtime(true) * 10000, -4);
              }
          }
          
          // Create order (inventory will be deducted when admin marks as complete)
          $stmt = $conn->prepare("INSERT INTO orders (order_id, user_id, inventory_id, quantity, size, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
          $size_for_order = !empty($size) ? $size : null;
          $stmt->bind_param("siiisd", $order_id, $_SESSION['user_id'], $item_id, $quantity, $size_for_order, $total_price);
          $stmt->execute();
          
          // Commit transaction
          $conn->commit();
          
          $success = "Your order has been submitted successfully. Please proceed to the cashier for payment.";
          
          // Refresh item data after successful order
          $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
          $stmt->bind_param("i", $item_id);
          $stmt->execute();
          $result = $stmt->get_result();
          $item = $result->fetch_assoc();
          
      } catch (Exception $e) {
          // Rollback transaction on error
          $conn->rollback();
          $error = "Error processing your order: " . $e->getMessage();
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
              <h1 class="text-2xl font-semibold text-gray-900">Order Item</h1>
              <div class="flex items-center">
                  <a href="profile.php" class="flex items-center">
                      <?php if (!empty($profile_pic) && file_exists('../' . $profile_pic)): ?>
                          <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-gray-300 hover:border-blue-500 transition-colors cursor-pointer">
                      <?php else: ?>
                          <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center border-2 border-gray-300 hover:border-blue-500 transition-colors cursor-pointer">
                              <i class="fas fa-user text-gray-600"></i>
                          </div>
                      <?php endif; ?>
                  </a>
                  <span class="text-gray-700 hidden sm:inline mr-2"><?php echo $_SESSION['user_name']; ?></span>
                  <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500" id="menu-button">
                      <span class="sr-only">Open menu</span>
                      <i class="fas fa-bars"></i>
                  </button>
              </div>
          </div>
      </header>
      
      <!-- Main content -->
      <main class="flex-1 overflow-y-auto p-4">
          <div class="max-w-3xl mx-auto">
              <?php if (!empty($error)): ?>
                  <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                      <p><?php echo $error; ?></p>
                  </div>
              <?php endif; ?>
              
              <?php if (!empty($success)): ?>
                  <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                      <p><?php echo $success; ?></p>
                      <div class="mt-4 flex space-x-4">
                          <a href="receipt.php?order_id=<?php echo $order_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                              <i class="fas fa-receipt mr-2"></i> View Receipt
                          </a>
                          <a href="orders.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                              <i class="fas fa-list mr-2"></i> View All Orders
                          </a>
                      </div>
                  </div>
              <?php endif; ?>
              
              <div class="bg-white rounded-lg shadow overflow-hidden">
                  <div class="md:flex">
                      <div class="md:flex-shrink-0 bg-gray-100 md:w-48 flex items-center justify-center p-4">
                          <?php if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])): ?>
                              <img src="<?php echo '../' . $item['image_path']; ?>" alt="<?php echo $item['name']; ?>" class="object-cover h-32 w-32">
                          <?php elseif (!empty($item['image_path'])): ?>
                              <img src="<?php echo $item['image_path']; ?>" alt="<?php echo $item['name']; ?>" class="object-cover h-32 w-32">
                          <?php else: ?>
                              <i class="fas fa-box text-gray-400 text-6xl"></i>
                          <?php endif; ?>
                      </div>
                      <div class="p-8 w-full">
                          <div class="uppercase tracking-wide text-sm text-emerald-600 font-semibold">
                              Order Details
                          </div>
                          <h2 class="mt-1 text-2xl font-bold text-gray-900"><?php echo $item['name']; ?></h2>
                          <p class="mt-2 text-gray-600"><?php echo $item['description']; ?></p>
                          
                          <div class="mt-4">
                              <p class="text-lg font-bold text-gray-900">₱<?php echo number_format($item['price'], 2); ?></p>
                              <p class="text-sm text-gray-500">
                                  Available Quantity: <?php echo $item['quantity']; ?>
                              </p>
                          </div>
                          
                          <?php if (empty($success) && $item['quantity'] > 0 && $item['in_stock']): ?>
                              <form method="POST" action="order_item.php?id=<?php echo $item_id; ?>" class="mt-6">
                                  <div class="space-y-4">
                                      <?php 
                                      // Check if item name contains clothing keywords
                                      $clothingKeywords = ['shirt', 't-shirt', 'tshirt', 'pants', 'shorts', 'jacket', 'hoodie', 'polo', 'jersey'];
                                      $needs_sizes = false;
                                      
                                      foreach ($clothingKeywords as $keyword) {
                                          if (stripos($item['name'], $keyword) !== false) {
                                              $needs_sizes = true;
                                              break;
                                          }
                                      }
                                      
                                      if ($needs_sizes): 
                                          $available_sizes = [];
                                          if (!empty($item['sizes'])) {
                                              $available_sizes = json_decode($item['sizes'], true);
                                          }
                                      ?>
                                          <div>
                                              <label for="size" class="block text-sm font-medium text-gray-700">Size</label>
                                              <select id="size" name="size" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm rounded-md">
                                                  <option value="">Select Size</option>
                                                  <?php if (!empty($available_sizes)): ?>
                                                      <?php foreach ($available_sizes as $size): ?>
                                                          <option value="<?php echo $size; ?>" <?php echo ($prefilled_size === $size) ? 'selected' : ''; ?>><?php echo $size; ?></option>
                                                      <?php endforeach; ?>
                                                  <?php else: ?>
                                                      <option value="XS" <?php echo ($prefilled_size === 'XS') ? 'selected' : ''; ?>>Extra Small (XS)</option>
                                                      <option value="S" <?php echo ($prefilled_size === 'S') ? 'selected' : ''; ?>>Small (S)</option>
                                                      <option value="M" <?php echo ($prefilled_size === 'M') ? 'selected' : ''; ?>>Medium (M)</option>
                                                      <option value="L" <?php echo ($prefilled_size === 'L') ? 'selected' : ''; ?>>Large (L)</option>
                                                      <option value="XL" <?php echo ($prefilled_size === 'XL') ? 'selected' : ''; ?>>Extra Large (XL)</option>
                                                      <option value="2XL" <?php echo ($prefilled_size === '2XL') ? 'selected' : ''; ?>>2XL</option>
                                                      <option value="3XL" <?php echo ($prefilled_size === '3XL') ? 'selected' : ''; ?>>3XL</option>
                                                  <?php endif; ?>
                                              </select>
                                          </div>
                                      <?php elseif (strpos(strtolower($item['name']), 'uniform') !== false || strpos(strtolower($item['description']), 'size') !== false): ?>
                                          <div>
                                              <label for="size" class="block text-sm font-medium text-gray-700">Size</label>
                                              <select id="size" name="size" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm rounded-md">
                                                  <option value="">Select Size</option>
                                                  <option value="S">Small (S)</option>
                                                  <option value="M">Medium (M)</option>
                                                  <option value="L">Large (L)</option>
                                                  <option value="XL">Extra Large (XL)</option>
                                              </select>
                                          </div>
                                      <?php endif; ?>
                                      
                                      <div>
                                          <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                                          <input type="number" name="quantity" id="quantity" min="1" max="<?php echo $item['quantity']; ?>" value="<?php echo $prefilled_quantity; ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                                      </div>
                                      
                                      <div class="pt-4 border-t border-gray-200">
                                          <div class="flex justify-between text-sm mb-1">
                                              <span>Price per item:</span>
                                              <span>₱<?php echo number_format($item['price'], 2); ?></span>
                                          </div>
                                          <div class="flex justify-between text-sm mb-1">
                                              <span>Quantity:</span>
                                              <span id="quantity-display">1</span>
                                          </div>
                                          <div class="flex justify-between font-bold text-lg pt-2 border-t">
                                              <span>Total:</span>
                                              <span id="total-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                          </div>
                                      </div>
                                      
                                      <div class="mt-6 flex justify-end space-x-3">
                                          <a href="inventory.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                              Cancel
                                          </a>
                                          <button type="button" id="submit-order-btn" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                              Submit Order
                                          </button>
                                      </div>
                                  </div>
                              </form>
                          <?php elseif (empty($success)): ?>
                              <div class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
                                  <div class="flex">
                                      <div class="flex-shrink-0">
                                          <i class="fas fa-exclamation-circle text-red-400"></i>
                                      </div>
                                      <div class="ml-3">
                                          <h3 class="text-sm font-medium text-red-800">Item unavailable</h3>
                                          <div class="mt-2 text-sm text-red-700">
                                              <p>This item is currently out of stock or unavailable for ordering.</p>
                                          </div>
                                          <div class="mt-4">
                                              <a href="inventory.php" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                  Return to inventory
                                              </a>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>
          </div>
      </main>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-gray-900">Confirm Order Submission</h3>
            </div>
        </div>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-4">Are you sure you want to submit your order?</p>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Item:</span>
                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($item['name']); ?></span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Quantity:</span>
                    <span class="text-sm text-gray-900" id="modal-quantity">1</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Price per item:</span>
                    <span class="text-sm text-gray-900">₱<?php echo number_format($item['price'], 2); ?></span>
                </div>
                <div class="flex justify-between items-center font-semibold text-lg pt-2 border-t border-gray-200">
                    <span class="text-gray-900">Total:</span>
                    <span class="text-blue-600" id="modal-total">₱<?php echo number_format($item['price'], 2); ?></span>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <p class="text-xs text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Once submitted, you will need to proceed to the cashier for payment.
                </p>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="button" id="cancel-order" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </button>
            <button type="button" id="confirm-order" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-check mr-2"></i>
                Yes, Submit Order
            </button>
        </div>
    </div>
</div>

<script>
  // Mobile menu toggle
  document.getElementById('menu-button').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('-translate-x-full');
  });
  
  // Update total price based on quantity
  const quantityInput = document.getElementById('quantity');
  const quantityDisplay = document.getElementById('quantity-display');
  const totalPrice = document.getElementById('total-price');
  const itemPrice = <?php echo $item['price']; ?>;
  
  if (quantityInput) {
      quantityInput.addEventListener('change', function() {
          const quantity = parseInt(this.value);
          if (quantityDisplay) {
              quantityDisplay.textContent = quantity;
          }
          if (totalPrice) {
              const total = quantity * itemPrice;
              totalPrice.textContent = '₱' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
          }
      });
  }
  
  // Confirmation modal functionality
  const submitOrderBtn = document.getElementById('submit-order-btn');
  const confirmationModal = document.getElementById('confirmationModal');
  const cancelOrderBtn = document.getElementById('cancel-order');
  const confirmOrderBtn = document.getElementById('confirm-order');
  const modalQuantity = document.getElementById('modal-quantity');
  const modalTotal = document.getElementById('modal-total');
  const orderForm = document.querySelector('form');
  
  // Show modal when submit button is clicked
  if (submitOrderBtn) {
      submitOrderBtn.addEventListener('click', function() {
          // Update modal with current values
          const currentQuantity = quantityInput ? parseInt(quantityInput.value) : 1;
          const currentTotal = currentQuantity * itemPrice;
          
          if (modalQuantity) {
              modalQuantity.textContent = currentQuantity;
          }
          if (modalTotal) {
              modalTotal.textContent = '₱' + currentTotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
          }
          
          // Show modal
          confirmationModal.classList.remove('hidden');
      });
  }
  
  // Hide modal when cancel is clicked
  if (cancelOrderBtn) {
      cancelOrderBtn.addEventListener('click', function() {
          confirmationModal.classList.add('hidden');
      });
  }
  
  // Submit form when confirmed
  if (confirmOrderBtn) {
      confirmOrderBtn.addEventListener('click', function() {
          if (orderForm) {
              orderForm.submit();
          }
      });
  }
  
  // Hide modal when clicking outside
  confirmationModal.addEventListener('click', function(e) {
      if (e.target === confirmationModal) {
          confirmationModal.classList.add('hidden');
      }
  });
  
  // Hide modal with Escape key
  document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !confirmationModal.classList.contains('hidden')) {
          confirmationModal.classList.add('hidden');
      }
  });
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>

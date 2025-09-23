<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "Order Items - CHMSU BAO";
$base_url = "..";

// Get all available inventory items
$query = "SELECT * FROM inventory ORDER BY name";
$result = $conn->query($query);

// Create images directory if it doesn't exist
$upload_dir = "../uploads/inventory/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Available Items</h1>
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
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-gray-900">Browse and Order Items</h2>
                        <p class="text-gray-500">Order school-related items from the Business Affairs Office</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-box text-gray-500"></i>
                        <span class="text-sm text-gray-500">
                            <?php 
                            $in_stock_query = "SELECT COUNT(*) as count FROM inventory WHERE in_stock = 1";
                            $in_stock_result = $conn->query($in_stock_query);
                            $in_stock_count = $in_stock_result->fetch_assoc()['count'];
                            echo $in_stock_count; 
                            ?> 
                            items available
                        </span>
                    </div>
                </div>
                
                <!-- Items grid -->
                <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($item = $result->fetch_assoc()): ?>
                            <div class="bg-white rounded-lg shadow overflow-hidden <?php echo $item['in_stock'] ? '' : 'opacity-70'; ?>">
                                <div class="p-4">
                                    <div class="h-48 w-full bg-gray-100 rounded-md flex items-center justify-center mb-4 overflow-hidden">
                                        <?php if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])): ?>
                                            <img src="<?php echo '../' . $item['image_path']; ?>" alt="<?php echo $item['name']; ?>" class="h-full w-full object-contain">
                                        <?php elseif (!empty($item['image_path'])): ?>
                                            <img src="<?php echo $item['image_path']; ?>" alt="<?php echo $item['name']; ?>" class="h-full w-full object-contain">
                                        <?php else: ?>
                                            <!-- Default image based on item type -->
                                            <?php 
                                            $icon_class = 'fa-box';
                                            $item_name_lower = strtolower($item['name']);
                                            
                                            if (strpos($item_name_lower, 'uniform') !== false) {
                                                $icon_class = 'fa-tshirt';
                                            } elseif (strpos($item_name_lower, 'cord') !== false) {
                                                $icon_class = 'fa-graduation-cap';
                                            } elseif (strpos($item_name_lower, 'id') !== false || strpos($item_name_lower, 'lace') !== false) {
                                                $icon_class = 'fa-id-card';
                                            } elseif (strpos($item_name_lower, 'pin') !== false) {
                                                $icon_class = 'fa-thumbtack';
                                            } elseif (strpos($item_name_lower, 'jacket') !== false) {
                                                $icon_class = 'fa-vest';
                                            } elseif (strpos($item_name_lower, 'patch') !== false || strpos($item_name_lower, 'logo') !== false) {
                                                $icon_class = 'fa-certificate';
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon_class; ?> text-gray-400 text-5xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo $item['name']; ?></h3>
                                    <p class="text-sm text-gray-500 mb-4"><?php echo $item['description']; ?></p>
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
                                <div class="px-4 py-3 bg-gray-50 text-right">
                                    <a href="order_item.php?id=<?php echo $item['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-emerald-700 <?php echo $item['in_stock'] ? '' : 'opacity-50 cursor-not-allowed'; ?>" <?php echo $item['in_stock'] ? '' : 'disabled'; ?>>
                                        <i class="fas fa-shopping-cart mr-2"></i> Order Item
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-8 bg-white rounded-lg shadow">
                            <i class="fas fa-box-open text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500">No inventory items available at the moment.</p>
                        </div>
                    <?php endif; ?>
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
</script>

<?php include '../includes/footer.php'; ?>

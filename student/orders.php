<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "My Orders - CHMSU BAO";
$base_url = "..";

// Get user's orders
$stmt = $conn->prepare("SELECT o.*, i.name as item_name, i.description as item_description 
                        FROM orders o 
                        JOIN inventory i ON o.inventory_id = i.id 
                        WHERE o.user_id = ? 
                        ORDER BY o.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">My Orders</h1>
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
                        <h2 class="text-xl font-bold tracking-tight text-gray-900">Order History</h2>
                        <p class="text-gray-500">View and manage your orders</p>
                    </div>
                    <a href="inventory.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                        <i class="fas fa-plus mr-2"></i> New Order
                    </a>
                </div>
                
                <!-- Orders list -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($order = $result->fetch_assoc()): ?>
                                <li>
                                    <div class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <p class="text-sm font-medium text-emerald-600 truncate"><?php echo $order['order_id']; ?></p>
                                                <div class="ml-2 flex-shrink-0 flex">
                                                    <?php if ($order['status'] == 'pending'): ?>
                                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                            Pending
                                                        </p>
                                                    <?php elseif ($order['status'] == 'approved'): ?>
                                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Approved
                                                        </p>
                                                    <?php elseif ($order['status'] == 'completed'): ?>
                                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            Completed
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                            Rejected
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="ml-2 flex-shrink-0 flex">
                                                <a href="receipt.php?order_id=<?php echo $order['order_id']; ?>" class="font-medium text-emerald-600 hover:text-emerald-500">
                                                    View Receipt
                                                </a>
                                            </div>
                                        </div>
                                        <div class="mt-2 sm:flex sm:justify-between">
                                            <div class="sm:flex">
                                                <p class="flex items-center text-sm text-gray-500">
                                                    <i class="fas fa-box flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400"></i>
                                                    <?php echo $order['item_name']; ?> (x<?php echo $order['quantity']; ?>)
                                                </p>
                                                <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                                    <i class="fas fa-money-bill-wave flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400"></i>
                                                    ₱<?php echo number_format($order['total_price'], 2); ?>
                                                </p>
                                            </div>
                                            <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                                <i class="fas fa-calendar flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400"></i>
                                                <p>
                                                    Ordered on <time datetime="<?php echo $order['created_at']; ?>"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></time>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="px-4 py-8 text-center">
                                <i class="fas fa-shopping-cart text-gray-400 text-4xl mb-2"></i>
                                <p class="text-gray-500">You haven't placed any orders yet.</p>
                                <div class="mt-4">
                                    <a href="inventory.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                        Browse Available Items
                                    </a>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
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

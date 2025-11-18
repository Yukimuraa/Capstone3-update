<?php
// Don't start a session here since it's already started in the main pages

// Determine the path to logout.php
$logout_path = dirname($_SERVER['PHP_SELF']);
$logout_path = preg_replace('/(\/admin|\/student|\/staff|\/external)$/', '', $logout_path);
if ($logout_path === '') {
    $logout_path = '/';
}
if (substr($logout_path, -1) !== '/') {
    $logout_path .= '/';
}
$logout_path .= 'logout.php?user_type=student';
?>

<div class="bg-blue-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out" id="sidebar">
    <div class="flex items-center space-x-2 px-4">
        <i class="fas fa-school text-yellow-400"></i>
        <div>
            <span class="text-xl font-bold">CHMSU BAO</span>
            <!-- <p class="text-xs text-gray-400">Student Portal</p> -->
        </div>
    </div>
    <nav>
        <a href="dashboard.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
            <i class="fas fa-home mr-2"></i>Dashboard
        </a>
        <a href="inventory.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
            <i class="fas fa-box mr-2"></i>Available Items
        </a>
        <a href="cart.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700 relative">
            <i class="fas fa-shopping-cart mr-2"></i>Shopping Cart
            <?php
            if (isset($_SESSION['user_id'])) {
                $cart_count_query = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
                $cart_count_query->bind_param("i", $_SESSION['user_id']);
                $cart_count_query->execute();
                $cart_count = $cart_count_query->get_result()->fetch_assoc()['count'];
                if ($cart_count > 0) {
                    echo '<span class="absolute top-2 right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-800 bg-yellow-400 rounded-full">' . $cart_count . '</span>';
                }
            }
            ?>
        </a>
        <a href="orders.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
            <i class="fas fa-list mr-2"></i>My Orders
        </a>
        <a href="bus.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
            <i class="fas fa-bus mr-2"></i>Bus Schedule
        </a>
        <!-- <a href="gym.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
            <i class="fas fa-calendar-alt mr-2"></i>Gym Bookings
        </a> -->
        <a href="profile.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
            <i class="fas fa-user mr-2"></i>My Profile
        </a>
        <!-- <a href="reports.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
            <i class="fas fa-chart-bar mr-2"></i>Reports
        </a> -->
        <a href="<?php echo $logout_path; ?>" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700 mt-6">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </nav>
</div>

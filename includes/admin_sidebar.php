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
$logout_path .= 'logout.php?user_type=admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar</title>

    <!-- Tailwind CSS CDN -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-dyZt8UU+W7CPVczCTjLRNV5LFSKNtXZb+h7PZGn8Nq0D/R+0vHf4kzjqPQ0PqI5YlfRqcfqY6DnmUz4K5eLqQA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Font Awesome CDN (This makes your icons work) -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-dyZt8UU+W7CPVczCTjLRNV5LFSKNtXZb+h7PZGn8Nq0D/R+0vHf4kzjqPQ0PqI5YlfRqcfqY6DnmUz4K5eLqQA==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->

</head>
<body>

<div class="bg-blue-900 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out" id="sidebar">
   <div class="flex items-center space-x-2 px-4">
       <i class="fas fa-school text-yellow-400"></i>
       <div>
           <span class="text-xl font-bold">CHMSU BAO</span>
           <!-- <p class="text-xs text-gray-400">Admin Portal</p> -->
       </div>
   </div>
   <nav>
       <a href="dashboard.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
           <i class="fas fa-home mr-2"></i>Dashboard
       </a>
       <!-- <a href="calendar.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
           <i class="fas fa-calendar-alt mr-2"></i>Calendar
       </a> -->
       <!-- <a href="facilities.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-emerald-700">
           <i class="fas fa-building mr-2"></i>Facilities
       </a> -->
       <a href="requests.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-clipboard-list mr-2"></i>Requests
       </a>
       <a href="../admin/gym_bookings.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-dumbbell mr-2"></i>Gym reservations
       </a>
       <a href="inventory.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-box mr-2"></i>Inventory
       </a>
       <a href="orders.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-shopping-cart mr-2"></i>Orders Receipt
       </a>
       <a href="bus.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-bus mr-2"></i>Bus Schedule
       </a>
       
       <!-- <a href="gym.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-calendar-alt mr-2"></i>Gym Bookings
       </a> -->
       <a href="users.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-users mr-2"></i>Users
       </a>
       <!-- <a href="reports.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-chart-bar mr-2"></i>Reports
       </a> -->
       <!-- <a href="settings.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800">
           <i class="fas fa-cog mr-2"></i>Settings
       </a> -->
       <a href="<?php echo $logout_path; ?>" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-800 mt-6">
           <i class="fas fa-sign-out-alt mr-2"></i>Logout
       </a>
   </nav>
</div>

</body>
</html>

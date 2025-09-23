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
$logout_path .= 'logout.php?user_type=external';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<div class="bg-blue-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out" id="sidebar">
    <div class="flex items-center space-x-2 px-4">
        <i class="fas fa-school text-yellow-400"></i>
        <div>
            <span class="text-xl font-bold">CHMSU BAO</span>
            <!-- <p class="text-xs text-gray-400">External Portal</p> -->
        </div>
    </div>
    <nav>
        <a href="dashboard.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-amber-700">
            <i class="fas fa-home mr-2"></i>Dashboard
        </a>
        <a href="requests.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-amber-700">
            <i class="fas fa-clipboard-list mr-2"></i>My Requests
        </a>
        <a href="gym.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-amber-700">
            <i class="fas fa-calendar-alt mr-2"></i>Gym Reservation
        </a>
        <a href="profile.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-amber-700">
            <i class="fas fa-user mr-2"></i>My Profile
        </a>
        <a href="<?php echo $logout_path; ?>" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-amber-700 mt-6">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </nav>
</div>

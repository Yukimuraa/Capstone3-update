<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHMSU Business Affairs Office</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-6 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fas fa-school text-emerald-600 text-2xl"></i>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">CHMSU</h1>
                    <p class="text-sm text-gray-500">Business Affairs Office</p>
                </div>
            </div>
            <div class="flex gap-4">
                <a href="login.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Login</a>
                <a href="register.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Register</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="container mx-auto px-4 py-16 text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            Business Affairs Office Information System
        </h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">
            A digital platform designed to improve transaction management at CHMSU. Submit requests, track status, and receive updates online.
        </p>
        <div class="flex justify-center gap-4">
    <a href="login.php" class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2">
        Get Started <i class="fas fa-chevron-right text-sm"></i>
    </a>
    <a href="#features" class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
        Learn More
    </a>
</div>
    </section>

    <!-- Features Section -->
    <section id="features" class="container mx-auto px-4 py-16">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Key Features</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-emerald-600 text-3xl mb-4">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Gym Reservations</h3>
                <p class="text-gray-600 mb-4">
                    Book the gymnasium for events, practices, and activities. Students, staff, and external organizations can reserve the gym online.
                </p>
                <a href="login.php" class="text-emerald-600 hover:underline flex items-center gap-1">
                    Book Now <i class="fas fa-chevron-right text-sm"></i>
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-emerald-600 text-3xl mb-4">
                    <i class="fas fa-bus"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Bus Scheduling</h3>
                <p class="text-gray-600 mb-4">
                    Reserve university buses for official trips and events. Staff can schedule bus usage through an easy-to-use interface.
                </p>
                <a href="login.php" class="text-emerald-600 hover:underline flex items-center gap-1">
                    Schedule Now <i class="fas fa-chevron-right text-sm"></i>
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-emerald-600 text-3xl mb-4">
                    <i class="fas fa-box"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Inventory Tracking</h3>
                <p class="text-gray-600 mb-4">
                    Manage school-related items like cords, PE uniforms, and logos. Track inventory levels and place orders through the digital system.
                </p>
                <a href="login.php" class="text-emerald-600 hover:underline flex items-center gap-1">
                    View Inventory <i class="fas fa-chevron-right text-sm"></i>
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-emerald-600 text-3xl mb-4">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Request Tracking</h3>
                <p class="text-gray-600 mb-4">
                    Submit and monitor the status of your requests. Receive real-time updates on approval status and notifications.
                </p>
                <a href="login.php" class="text-emerald-600 hover:underline flex items-center gap-1">
                    Submit Request <i class="fas fa-chevron-right text-sm"></i>
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-emerald-600 text-3xl mb-4">
                    <i class="fas fa-school"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">External User Access</h3>
                <p class="text-gray-600 mb-4">
                    Allow external organizations to book facilities. Schools and organizations can request to use the gymnasium for events.
                </p>
                <a href="register.php" class="text-emerald-600 hover:underline flex items-center gap-1">
                    Register <i class="fas fa-chevron-right text-sm"></i>
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-emerald-600 text-3xl mb-4">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Automated Notifications</h3>
                <p class="text-gray-600 mb-4">
                    Get notified about request approvals, reservation confirmations, and other important updates automatically.
                </p>
                <a href="login.php" class="text-emerald-600 hover:underline flex items-center gap-1">
                    Learn More <i class="fas fa-chevron-right text-sm"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-school"></i> CHMSU
                    </h3>
                    <p class="text-gray-400">
                        Carlos Hilado Memorial State University<br />
                        Business Affairs Office Information System
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="login.php" class="text-gray-400 hover:text-white">Login</a></li>
                        <li><a href="register.php" class="text-gray-400 hover:text-white">Register</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white">Features</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contact</h3>
                    <p class="text-gray-400">
                        Business Affairs Office<br />
                        CHMSU Campus<br />
                        Email: bao@chmsu.edu.ph<br />
                        Phone: (123) 456-7890
                    </p>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date("Y"); ?> Carlos Hilado Memorial State University. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>


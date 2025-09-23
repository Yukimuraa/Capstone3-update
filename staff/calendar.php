<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is staff
require_staff();

// Get user data for the staff
$user_id = $_SESSION['user_sessions']['staff']['user_id'];
$user_name = $_SESSION['user_sessions']['staff']['user_name'];

$page_title = "Calendar - CHMSU BAO";
$base_url = "..";

// Get all facilities
$facilities_query = "SELECT * FROM facilities WHERE status = 'active'";
$facilities = $conn->query($facilities_query);

// Get bookings for the current month
$current_month = date('Y-m');
$bookings_query = "SELECT b.*, f.name as facility_name, u.name as user_name 
                  FROM bookings b 
                  JOIN facilities f ON b.facility_id = f.id 
                  JOIN users u ON b.user_id = u.id 
                  WHERE DATE_FORMAT(b.booking_date, '%Y-%m') = ?";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("s", $current_month);
$stmt->execute();
$bookings = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/staff_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Calendar</h1>
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
                <!-- Calendar Controls -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-4">
                                <h2 class="text-lg font-medium text-gray-900"><?php echo date('F Y'); ?></h2>
                                <div class="flex space-x-2">
                                    <button id="prevMonth" class="p-2 rounded-md hover:bg-gray-100">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button id="nextMonth" class="p-2 rounded-md hover:bg-gray-100">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <select id="facilityFilter" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                    <option value="">All Facilities</option>
                                    <?php while ($facility = $facilities->fetch_assoc()): ?>
                                        <option value="<?php echo $facility['id']; ?>"><?php echo $facility['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calendar Grid -->
                    <div class="p-4">
                        <div class="grid grid-cols-7 gap-px bg-gray-200">
                            <?php
                            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            foreach ($days as $day):
                            ?>
                                <div class="bg-gray-50 p-2 text-center text-sm font-medium text-gray-500">
                                    <?php echo $day; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php
                            $first_day = date('Y-m-01');
                            $last_day = date('Y-m-t');
                            $first_day_of_week = date('w', strtotime($first_day));
                            
                            // Add empty cells for days before the first day of the month
                            for ($i = 0; $i < $first_day_of_week; $i++):
                            ?>
                                <div class="bg-white p-2 h-24"></div>
                            <?php endfor; ?>
                            
                            <?php
                            // Add cells for each day of the month
                            for ($day = 1; $day <= date('t'); $day++):
                                $current_date = date('Y-m-d', strtotime("$first_day +" . ($day - 1) . " days"));
                                $day_bookings = [];
                                
                                // Filter bookings for this day
                                $bookings->data_seek(0);
                                while ($booking = $bookings->fetch_assoc()) {
                                    if (date('Y-m-d', strtotime($booking['booking_date'])) === $current_date) {
                                        $day_bookings[] = $booking;
                                    }
                                }
                            ?>
                                <div class="bg-white p-2 h-24 relative">
                                    <span class="text-sm font-medium text-gray-900"><?php echo $day; ?></span>
                                    <div class="mt-1 space-y-1">
                                        <?php foreach ($day_bookings as $booking): ?>
                                            <div class="text-xs p-1 rounded bg-blue-100 text-blue-800 truncate" 
                                                 title="<?php echo $booking['facility_name'] . ' - ' . $booking['user_name']; ?>">
                                                <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                                <?php echo $booking['facility_name']; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Legend</h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-100 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-500">Booked</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-100 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-500">Available</span>
                        </div>
                    </div>
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
    
    // Calendar navigation
    document.getElementById('prevMonth').addEventListener('click', function() {
        // Add logic to navigate to previous month
    });
    
    document.getElementById('nextMonth').addEventListener('click', function() {
        // Add logic to navigate to next month
    });
    
    // Facility filter
    document.getElementById('facilityFilter').addEventListener('change', function() {
        // Add logic to filter calendar by facility
    });
</script>

<?php include '../includes/footer.php'; ?> 
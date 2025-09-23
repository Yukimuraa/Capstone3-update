<?php
// student/bus.php
require_once '../config/database.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = $_POST['client'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $date_covered = $_POST['date_covered'] ?? '';
    $vehicle = $_POST['vehicle'] ?? '';
    $bus_no = $_POST['bus_no'] ?? '';
    $no_of_days = $_POST['no_of_days'] ?? '';
    $no_of_vehicles = $_POST['no_of_vehicles'] ?? '';

    if (empty($client) || empty($destination) || empty($purpose) || empty($date_covered) || empty($vehicle) || empty($bus_no) || empty($no_of_days) || empty($no_of_vehicles)) {
        $error = 'All fields are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO bus_schedules (client, destination, purpose, date_covered, vehicle, bus_no, no_of_days, no_of_vehicles, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssii", $client, $destination, $purpose, $date_covered, $vehicle, $bus_no, $no_of_days, $no_of_vehicles);
        if ($stmt->execute()) {
            $success = 'Bus schedule submitted successfully!';
        } else {
            $error = 'Error saving schedule: ' . $conn->error;
        }
    }
}
$title = "Bus Schedule";
include '../includes/header.php'; // If you have a header include
?>
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">Bus Schedule Request Form</h2>
    <div class="bg-white shadow-md rounded-lg p-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <table class="min-w-full text-sm">
                <tr><td class="font-semibold">Client:</td><td><input type="text" name="client" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['client']) ? htmlspecialchars($_POST['client']) : 'OSAS'; ?>" required></td></tr>
                <tr><td class="font-semibold">Destination:</td><td><input type="text" name="destination" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : 'Talisay - Binalbagan'; ?>" required></td></tr>
                <tr><td class="font-semibold">Purpose:</td><td><input type="text" name="purpose" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : 'Bayanihan'; ?>" required></td></tr>
                <tr><td class="font-semibold">Date Covered:</td><td><input type="date" name="date_covered" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['date_covered']) ? htmlspecialchars($_POST['date_covered']) : ''; ?>" required></td></tr>
                <tr><td class="font-semibold">Vehicle:</td><td><input type="text" name="vehicle" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['vehicle']) ? htmlspecialchars($_POST['vehicle']) : 'Bus'; ?>" required></td></tr>
                <tr><td class="font-semibold">Bus No.:</td><td><input type="text" name="bus_no" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['bus_no']) ? htmlspecialchars($_POST['bus_no']) : '1'; ?>" required></td></tr>
                <tr><td class="font-semibold">No. of Days:</td><td><input type="number" name="no_of_days" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['no_of_days']) ? htmlspecialchars($_POST['no_of_days']) : '1'; ?>" required></td></tr>
                <tr><td class="font-semibold">No. of Vehicles:</td><td><input type="number" name="no_of_vehicles" class="border rounded px-2 py-1 w-full" value="<?php echo isset($_POST['no_of_vehicles']) ? htmlspecialchars($_POST['no_of_vehicles']) : '3'; ?>" required></td></tr>
            </table>
            <div class="mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Submit Schedule</button>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; // If you have a footer include ?> 
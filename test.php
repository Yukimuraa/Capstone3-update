<?php
include("connection.php");
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];

    $service = mysqli_real_escape_string($conn, $_POST["service"]);
    $service_details = mysqli_real_escape_string($conn, $_POST["service-details"]);
    $booking_status = mysqli_real_escape_string($conn, $_POST["booking-status"]);

    $insertQuery = "INSERT INTO bookings (user_id, service, service_details, booking_status) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);

    if (!$insertStmt) {
        die("Error in prepare statement: " . $conn->error);
    }

    $insertStmt->bind_param("isss", $user_id, $service, $service_details, $booking_status);

    if ($insertStmt->execute()) {
        echo "Booking submitted successfully. Thank you for booking. Please wait for our message via email.";

        if (!isset($_SESSION['email'])) {
            echo "Email address not found in session.";
            exit;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ibanezjayniel913@gmail.com';
            $mail->Password = 'yjorjllngtrpmuef';
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';
            $mail->isHTML(true);
            $mail->setFrom('ibanezjayniel913@gmail.com', 'Jayniel Ibanez');
            $mail->addAddress($_SESSION['email']); 
            $mail->Subject = 'Booking Confirmation';

            
            $mail->Body = '
                <html>
                <head>
                    <style>
                        
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f2f2f2;
                        }
                        .container {
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                            background-color: #fff;
                            border-radius: 10px;
                            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                        }
                        h2 {
                            color: #333;
                        }
                        p {
                            color: #666;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>Booking Confirmation</h2>
                        <p>Your booking has been confirmed. Details:</p>
                        <p><strong>Service:</strong> ' . $service . '</p>
                        <p><strong>Service Details:</strong> ' . $service_details . '</p>
                        <p><strong>Booking Status:</strong> ' . $booking_status . '</p>
                    </div>
                </body>
                </html>
            ';

            $mail->send();
            echo 'Thank you for booking!';
        } catch (Exception $e) {
            echo "Confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Error: " . $insertStmt->error;
    }

    $insertStmt->close();
}
?>
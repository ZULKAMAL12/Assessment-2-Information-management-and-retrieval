<?php
session_start();
include('includes/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if booking_id is provided in the URL
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header('Location: home.php');
    exit();
}

$booking_id = intval($_GET['booking_id']);

// Fetch booking details
$query = "
    SELECT 
        b.BookingID, b.CheckInDate, b.CheckOutDate, b.NumberOfGuest, b.TotalPrice, b.BookingStatus, 
        r.RoomType, r.PricePerNight, r.Capacity,
        c.Name AS CustomerName, c.Email AS CustomerEmail
    FROM Booking b
    INNER JOIN BookingRoom br ON b.BookingID = br.BookingID
    INNER JOIN Room r ON br.RoomID = r.RoomID
    INNER JOIN Customer c ON b.CustomerID = c.CustomerID
    WHERE b.BookingID = $booking_id AND b.CustomerID = {$_SESSION['user_id']}
";

$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    echo "Booking not found or you do not have permission to view this booking.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - TSC Hotel Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1rem 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="home.php">TSC Hotel Booking</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        <h1 class="text-center">Booking Confirmation</h1>
        <p class="text-center">Thank you for booking with us, <?php echo htmlspecialchars($booking['CustomerName']); ?>!</p>
        <div class="card shadow mt-4">
            <div class="card-body">
                <h4>Booking Details</h4>
                <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['BookingID']); ?></p>
                <p><strong>Room Type:</strong> <?php echo htmlspecialchars($booking['RoomType']); ?></p>
                <p><strong>Price Per Night:</strong> RM <?php echo htmlspecialchars($booking['PricePerNight']); ?></p>
                <p><strong>Check-In Date:</strong> <?php echo htmlspecialchars($booking['CheckInDate']); ?></p>
                <p><strong>Check-Out Date:</strong> <?php echo htmlspecialchars($booking['CheckOutDate']); ?></p>
                <p><strong>Total Price:</strong> RM <?php echo htmlspecialchars($booking['TotalPrice']); ?></p>
                <p><strong>Guests:</strong> <?php echo htmlspecialchars($booking['NumberOfGuest']); ?> (Capacity: <?php echo htmlspecialchars($booking['Capacity']); ?>)</p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($booking['BookingStatus']); ?></p>
            </div>
        </div>
        <a href="home.php" class="btn btn-primary mt-4 w-100">Back to Home</a>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 TSC Hotel Booking. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

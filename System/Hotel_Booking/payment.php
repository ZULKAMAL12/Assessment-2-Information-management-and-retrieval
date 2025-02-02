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

// Fetch booking detail
$query = "
    SELECT b.BookingID, b.TotalPrice, b.BookingStatus,
           r.RoomType, r.PricePerNight,
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
    echo "Booking not found .";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $amount = $booking['TotalPrice'];

    // Insert payment details into Payment table
    $payment_query = "
        INSERT INTO Payment (BookingID, Amount, PaymentMethod, PaymentStatus)
        VALUES ($booking_id, $amount, '$payment_method', 'Paid')
    ";

    if (mysqli_query($conn, $payment_query)) {
        // Update booking status to Confirmed
        $update_booking_query = "
            UPDATE Booking
            SET BookingStatus = 'Confirmed'
            WHERE BookingID = $booking_id
        ";
        mysqli_query($conn, $update_booking_query);

        // Redirect to confirmation page
        header("Location: confirmation.php?booking_id=$booking_id");
        exit();
    } else {
        $error = "Failed to process payment. Please try Again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - TSC Hotel Booking</title>
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
        <h1 class="text-center">Payment</h1>
        <p class="text-center">Thank you for booking with us, <?php echo htmlspecialchars($booking['CustomerName']); ?>!</p>
        <div class="card shadow mt-4">
            <div class="card-body">
                <h4>Booking Details</h4>
                <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['BookingID']); ?></p>
                <p><strong>Room Type:</strong> <?php echo htmlspecialchars($booking['RoomType']); ?></p>
                <p><strong>Total Price:</strong> RM <?php echo htmlspecialchars($booking['TotalPrice']); ?></p>
            </div>
        </div>

        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select name="payment_method" class="form-control" required>
                    <option value="Credit Card">Credit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100">Confirm Payment</button>
        </form>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 TSC Hotel Booking</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
include('includes/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get the room ID from the URL
if (!isset($_GET['room_id']) || empty($_GET['room_id'])) {
    header('Location: home.php');
    exit();
}
$room_id = intval($_GET['room_id']);

// Fetch room details
$query = "SELECT * FROM Room WHERE RoomID = $room_id AND AvailabilityStatus = 1";
$result = mysqli_query($conn, $query);
$room = mysqli_fetch_assoc($result);

if (!$room) {
    echo "Room not found or unavailable.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_availability'])) {
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);

    // Validate dates
    if (strtotime($check_in) >= strtotime($check_out)) {
        $error = "Check-out date must be after the check-in date.";
    } else {
        // Check room availability
        $availability_query = "
            SELECT * FROM BookingRoom br
            INNER JOIN Booking b ON br.BookingID = b.BookingID
            WHERE br.RoomID = $room_id
            AND (
                (b.CheckInDate <= '$check_out' AND b.CheckOutDate >= '$check_in')
            )
        ";
        $availability_result = mysqli_query($conn, $availability_query);

        if (mysqli_num_rows($availability_result) > 0) {
            $error = "Room is not available for the selected dates.";
        } else {
            // Calculate total price
            $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
            $total_price = $days * $room['PricePerNight'];
            $available = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $total_price = mysqli_real_escape_string($conn, $_POST['total_price']);

    // Insert booking into Booking table
    $user_id = $_SESSION['user_id'];
    $booking_query = "
        INSERT INTO Booking (CustomerID, CheckInDate, CheckOutDate, NumberOfGuest, TotalPrice)
        VALUES ($user_id, '$check_in', '$check_out', {$room['Capacity']}, $total_price)
    ";

    if (mysqli_query($conn, $booking_query)) {
        $booking_id = mysqli_insert_id($conn);

        // Insert into BookingRoom table
        $booking_room_query = "
            INSERT INTO BookingRoom (BookingID, RoomID, Quantity)
            VALUES ($booking_id, $room_id, 1)
        ";
        if (mysqli_query($conn, $booking_room_query)) {
            header("Location: confirmation.php?booking_id=$booking_id");
            exit();
        } else {
            $error = "Failed to assign the room to the booking.";
        }
    } else {
        $error = "Failed to create the booking. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - TSC Hotel Booking</title>
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
        <h1 class="text-center">Book <?php echo htmlspecialchars($room['RoomType']); ?></h1>
        <p class="text-center">Price: RM <?php echo htmlspecialchars($room['PricePerNight']); ?> / Night</p>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if (!isset($available)) { ?>
            <!-- Availability Form -->
            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="check_in" class="form-label">Check-in Date</label>
                    <input type="date" name="check_in" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="check_out" class="form-label">Check-out Date</label>
                    <input type="date" name="check_out" class="form-control" required>
                </div>
                <button type="submit" name="check_availability" class="btn btn-primary w-100">Check Availability</button>
            </form>
        <?php } else { ?>
            <!-- Confirmation Form -->
            <div class="alert alert-success">
                Room is available! Total Price: RM <?php echo htmlspecialchars($total_price); ?>
            </div>
            <form method="POST" class="mt-4">
                <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
                <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
                <input type="hidden" name="total_price" value="<?php echo htmlspecialchars($total_price); ?>">
                <button type="submit" name="confirm_booking" class="btn btn-success w-100">Confirm Booking</button>
            </form>
        <?php } ?>
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

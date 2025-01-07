<?php
session_start();
include('includes/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Guest capacity based on room type
$guestCapacity = [
    'Family Room' => 4,
    'Suite' => 2,
    'Standard Deluxe' => 2,
];

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Cancel booking and remove room assignment
    $cancel_query = "UPDATE Booking SET BookingStatus = 'Cancelled' WHERE BookingID = $booking_id AND CustomerID = $user_id";
    if (mysqli_query($conn, $cancel_query)) {
        $delete_booking_room = "DELETE FROM BookingRoom WHERE BookingID = $booking_id";
        mysqli_query($conn, $delete_booking_room);
        $success = "Booking has been cancelled successfully.";
    } else {
        $error = "Failed to cancel the booking. Please try again.";
    }
}

// Handle booking update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $new_check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $number_of_guests = intval($_POST['number_of_guests']);
    $room_type = mysqli_real_escape_string($conn, $_POST['room_type']);
    $maxGuests = isset($guestCapacity[$room_type]) ? $guestCapacity[$room_type] : 1;

    if (strtotime($new_check_in) >= strtotime($new_check_out)) {
        $error = "Check-out date must be after the check-in date.";
    } elseif ($number_of_guests < 1 || $number_of_guests > $maxGuests) {
        $error = "Number of guests must be between 1 and $maxGuests.";
    } else {
        $update_query = "
            UPDATE Booking 
            SET CheckInDate = '$new_check_in', CheckOutDate = '$new_check_out', NumberOfGuest = $number_of_guests 
            WHERE BookingID = $booking_id AND CustomerID = $user_id AND BookingStatus IN ('Pending', 'Confirmed')
        ";
        if (mysqli_query($conn, $update_query)) {
            $success = "Booking updated successfully.";
        } else {
            $error = "Failed to update the booking. Please try again.";
        }
    }
}

// Fetch user bookings
$booking_query = "
    SELECT b.BookingID, b.CheckInDate, b.CheckOutDate, b.TotalPrice, b.NumberOfGuest, b.BookingStatus, 
           r.RoomType
    FROM Booking b
    INNER JOIN BookingRoom br ON b.BookingID = br.BookingID
    INNER JOIN Room r ON br.RoomID = r.RoomID
    WHERE b.CustomerID = $user_id
    ORDER BY b.BookingDate DESC
";
$bookings = mysqli_query($conn, $booking_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - TSC Hotel Booking</title>
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
        <h1 class="text-center">Manage Your Bookings</h1>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <?php if (isset($success)) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>

        <div class="table-responsive mt-4">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Booking ID</th>
                        <th>Room Type</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Guests</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($bookings && mysqli_num_rows($bookings) > 0) {
                        while ($booking = mysqli_fetch_assoc($bookings)) {
                            $maxGuests = isset($guestCapacity[$booking['RoomType']]) ? $guestCapacity[$booking['RoomType']] : 1;

                            echo "<tr>
                                <td>" . htmlspecialchars($booking['BookingID']) . "</td>
                                <td>" . htmlspecialchars($booking['RoomType']) . "</td>
                                <td>" . htmlspecialchars($booking['CheckInDate']) . "</td>
                                <td>" . htmlspecialchars($booking['CheckOutDate']) . "</td>
                                <td>" . htmlspecialchars($booking['NumberOfGuest']) . "</td>
                                <td>RM " . htmlspecialchars($booking['TotalPrice']) . "</td>
                                <td>" . htmlspecialchars($booking['BookingStatus']) . "</td>
                                <td>";
                            if (in_array($booking['BookingStatus'], ['Pending', 'Confirmed'])) {
                                echo "
                                <form method='POST' class='d-inline'>
                                    <input type='hidden' name='booking_id' value='" . htmlspecialchars($booking['BookingID']) . "'>
                                    <button type='submit' name='cancel_booking' class='btn btn-danger btn-sm'>Cancel</button>
                                </form>
                                <form method='POST' class='d-inline mt-2'>
                                    <input type='hidden' name='booking_id' value='" . htmlspecialchars($booking['BookingID']) . "'>
                                    <input type='hidden' name='room_type' value='" . htmlspecialchars($booking['RoomType']) . "'>
                                    <div class='mb-2'>
                                        <input type='date' name='check_in' class='form-control form-control-sm mb-1' required placeholder='New Check-In'>
                                        <input type='date' name='check_out' class='form-control form-control-sm mb-1' required placeholder='New Check-Out'>
                                        <input type='number' name='number_of_guests' class='form-control form-control-sm' min='1' max='$maxGuests' value='" . htmlspecialchars($booking['NumberOfGuest']) . "' required>
                                    </div>
                                    <button type='submit' name='update_booking' class='btn btn-warning btn-sm'>Update</button>
                                </form>";
                            } else {
                                echo "<span class='text-muted'>No Actions Available</span>";
                            }
                            echo "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>No bookings found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
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

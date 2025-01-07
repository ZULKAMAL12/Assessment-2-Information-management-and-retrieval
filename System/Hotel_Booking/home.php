<?php
session_start();
include('includes/db.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Room images mapped by RoomType
$roomImages = [
    'Family Room' => 'assets/img/family_room.jpg',
    'Suite' => 'assets/img/suite.jpg',
    'Standard Deluxe' => 'assets/img/standard_deluxe.jpg',
];

// Guest capacity mapped by RoomType
$roomGuestCapacity = [
    'Family Room' => 4,
    'Suite' => 2,
    'Standard Deluxe' => 2,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - TSC Hotel Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero {
            background: url('assets/images/hotel_hero.jpg') no-repeat center center;
            background-size: cover;
            height: 300px; /* Adjusted height */
            display: flex;
            align-items: center;
            justify-content: center;
            color: black; /* Set text color to black */
            text-align: center;
        }
        .room-img {
            width: 100%; /* Full width */
            height: 200px; /* Fixed height */
            object-fit: cover; /* Ensures the image scales without distortion */
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="home.php">TSC Hotel Booking</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_booking.php">Manage Booking</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                    
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1>Welcome to TSC Hotel Booking</h1>
            <p>Your luxurious stay begins here, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        </div>
    </div>

    <!-- Rooms Section -->
    <section class="container my-5">
        <h2 class="text-center mb-4">Available Rooms</h2>
        <div class="row">
            <?php
            // Fetch available rooms from the database
            $query = "SELECT * FROM Room WHERE AvailabilityStatus = 1";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($room = mysqli_fetch_assoc($result)) {
                    $image = isset($roomImages[$room['RoomType']]) ? $roomImages[$room['RoomType']] : 'assets/images/default_room.jpg';
                    $guestCapacity = isset($roomGuestCapacity[$room['RoomType']]) ? $roomGuestCapacity[$room['RoomType']] : 1;

                    echo "
                    <div class='col-md-4'>
                        <div class='card mb-4'>
                            <img src='" . htmlspecialchars($image) . "' class='card-img-top room-img' alt='" . htmlspecialchars($room['RoomType']) . "'>
                            <div class='card-body'>
                                <h5 class='card-title'>" . htmlspecialchars($room['RoomType']) . "</h5>
                                <p class='card-text'>Price: RM " . htmlspecialchars($room['PricePerNight']) . " / Night</p>
                                <p class='card-text'>Guests: Up to " . htmlspecialchars($guestCapacity) . "</p>
                                <p class='card-text'>Available Rooms: " . htmlspecialchars($room['Capacity']) . "</p>
                                <a href='book.php?room_id=" . htmlspecialchars($room['RoomID']) . "' class='btn btn-primary'>Book Now</a>
                            </div>
                        </div>
                    </div>
                    ";
                }
            } else {
                echo "<p class='text-center'>No rooms available at the moment.</p>";
            }
            ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2025 TSC Hotel Booking</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 07, 2025 at 06:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tsc_hotel_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `BookingID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `CheckInDate` date NOT NULL,
  `CheckOutDate` date NOT NULL,
  `BookingStatus` varchar(50) NOT NULL DEFAULT 'Pending',
  `NumberOfGuest` int(11) NOT NULL,
  `TotalPrice` decimal(10,2) DEFAULT NULL,
  `BookingDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`BookingID`, `CustomerID`, `CheckInDate`, `CheckOutDate`, `BookingStatus`, `NumberOfGuest`, `TotalPrice`, `BookingDate`) VALUES
(1, 1, '2025-01-04', '2025-01-05', 'Pending', 1, 150.00, '2025-01-03 23:00:37'),
(2, 1, '2025-01-04', '2025-01-06', 'Pending', 1, 300.00, '2025-01-03 23:02:10'),
(3, 1, '2025-01-07', '2025-01-09', 'Cancelled', 1, 300.00, '2025-01-03 23:11:08'),
(4, 1, '2025-01-06', '2025-01-07', 'Cancelled', 1, 150.00, '2025-01-05 00:36:49');

--
-- Triggers `booking`
--
DELIMITER $$
CREATE TRIGGER `RestoreRoomAvailability` AFTER DELETE ON `booking` FOR EACH ROW BEGIN
    -- Step 1: Delete associated rows in BookingRoom
    DELETE FROM BookingRoom
    WHERE BookingID = OLD.BookingID;

    -- Step 2: Update Room availability dynamically
    UPDATE Room
    SET AvailabilityStatus = CASE
        -- If total bookings for a room are equal or exceed capacity, mark it unavailable
        WHEN (
            SELECT COALESCE(SUM(br.Quantity), 0)
            FROM BookingRoom br
            WHERE br.RoomID = Room.RoomID
        ) >= Room.Capacity THEN 0
        -- Otherwise, mark it available
        ELSE 1
    END
    WHERE RoomID IN (
        -- Only update affected RoomIDs
        SELECT DISTINCT br.RoomID
        FROM BookingRoom br
        WHERE br.RoomID IN (
            SELECT RoomID
            FROM BookingRoom
            WHERE BookingID = OLD.BookingID
        )
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bookingroom`
--

CREATE TABLE `bookingroom` (
  `BookingRoomID` int(11) NOT NULL,
  `BookingID` int(11) NOT NULL,
  `RoomID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookingroom`
--

INSERT INTO `bookingroom` (`BookingRoomID`, `BookingID`, `RoomID`, `Quantity`) VALUES
(1, 1, 1, 1),
(2, 2, 1, 1),
(3, 3, 1, 1),
(4, 4, 1, 1);

--
-- Triggers `bookingroom`
--
DELIMITER $$
CREATE TRIGGER `UpdateRoomAvailabilityAfterInsert` AFTER INSERT ON `bookingroom` FOR EACH ROW BEGIN
    -- Update room availability based on total bookings
    UPDATE Room
    SET AvailabilityStatus = CASE
        WHEN (
            SELECT COALESCE(SUM(br.Quantity), 0)
            FROM BookingRoom br
            WHERE br.RoomID = Room.RoomID
        ) >= Room.Capacity THEN 0
        ELSE 1
    END
    WHERE RoomID = NEW.RoomID;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `UpdateRoomAvailabilityAfterUpdate` AFTER UPDATE ON `bookingroom` FOR EACH ROW BEGIN
    -- Update room availability based on total bookings
    UPDATE Room
    SET AvailabilityStatus = CASE
        WHEN (
            SELECT COALESCE(SUM(br.Quantity), 0)
            FROM BookingRoom br
            WHERE br.RoomID = Room.RoomID
        ) >= Room.Capacity THEN 0
        ELSE 1
    END
    WHERE RoomID IN (NEW.RoomID, OLD.RoomID);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `CustomerID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`CustomerID`, `Name`, `Email`, `Password`, `PhoneNumber`, `CreatedAt`) VALUES
(1, 'user', 'user@example.com', '$2y$10$E2zGNh0mPWD/hnTE49GdFO8sHT2qjLUmyb57zWudxirAlVaGpBZ5O', '0133808802', '2025-01-03 23:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `BookingID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `PaymentDate` datetime DEFAULT current_timestamp(),
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `PaymentStatus` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`PaymentID`, `BookingID`, `Amount`, `PaymentDate`, `PaymentMethod`, `PaymentStatus`) VALUES
(1, 3, 300.00, '2025-01-03 23:11:18', 'Bank Transfer', 'Paid'),
(2, 4, 150.00, '2025-01-05 00:36:57', 'Bank Transfer', 'Paid');

--
-- Triggers `payment`
--
DELIMITER $$
CREATE TRIGGER `UpdateBookingStatusOnPayment` AFTER INSERT ON `payment` FOR EACH ROW BEGIN
    UPDATE Booking
    SET BookingStatus = 'Confirmed'
    WHERE BookingID = NEW.BookingID AND NEW.PaymentStatus = 'Paid';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `RoomID` int(11) NOT NULL,
  `RoomType` varchar(50) NOT NULL,
  `PricePerNight` decimal(10,2) NOT NULL,
  `Capacity` int(11) NOT NULL,
  `AvailabilityStatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`RoomID`, `RoomType`, `PricePerNight`, `Capacity`, `AvailabilityStatus`) VALUES
(1, 'Family Room', 150.00, 3, 1),
(2, 'Suite', 200.00, 2, 1),
(3, 'Standard Deluxe', 100.00, 3, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `CustomerID` (`CustomerID`);

--
-- Indexes for table `bookingroom`
--
ALTER TABLE `bookingroom`
  ADD PRIMARY KEY (`BookingRoomID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `RoomID` (`RoomID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`CustomerID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `BookingID` (`BookingID`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`RoomID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bookingroom`
--
ALTER TABLE `bookingroom`
  MODIFY `BookingRoomID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `RoomID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`) ON DELETE CASCADE;

--
-- Constraints for table `bookingroom`
--
ALTER TABLE `bookingroom`
  ADD CONSTRAINT `bookingroom_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookingroom_ibfk_2` FOREIGN KEY (`RoomID`) REFERENCES `room` (`RoomID`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

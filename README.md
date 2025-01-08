# Assessment-2-Information-management-and-retrieval

System/webiste Devolopment for TSC HOTEL BOOKING
Using PHP as Backend Language 
Mysql XAMPP Localhost Database

**Features**
Customer Management:
-Register new customers.
-Log in securely using hashed passwords.

Room Management:
-View available rooms in real-time.
-Manage room details (type, price, capacity, availability).

Booking Management:
-Create new bookings.
-Update or cancel existing bookings.
-View customer booking details.

Payment Processing:
-Handle booking payments securely.
-Track payment status.

Automated Updates:
-Dynamic room availability using database triggers.
-Views for active bookings and associated details.

**API Endpoints**
Authentication
-POST /register - Register a new customer.
-POST /login - Log in to an existing account.

Room Management
-GET /rooms - Retrieve available rooms.

Booking Management
-POST /bookings - Create a new booking.
-GET /bookings - Retrieve all bookings for a customer.
-PUT /bookings/{id} - Update a booking.
-DELETE /bookings/{id} - Cancel a booking.

Payment Processing
POST /payments - Make a payment for a booking.

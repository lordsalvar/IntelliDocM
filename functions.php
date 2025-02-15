<?php
// functions.php

// Sanitize and format form values
function setValue($data)
{
    return isset($data) && !empty($data) ? htmlspecialchars($data) : '';
}

function setReadonly($data)
{
    return isset($data) && !empty($data) ? 'readonly' : '';
}

function sanitize_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Data-fetching functions

function getClubData($conn, $user_id)
{
    $sql = "SELECT c.club_name, c.acronym, c.club_type, cm.designation, cm.club_id
            FROM clubs c    
            JOIN club_memberships cm ON c.club_id = cm.club_id
            WHERE cm.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getApplicantDetails($conn, $user_id)
{
    $sql = "SELECT full_name AS applicant_name, contact AS applicant_contact FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: ['applicant_name' => '', 'applicant_contact' => ''];
}

function getModeratorData($conn, $club_id)
{
    $sql = "SELECT u.full_name AS moderator_name, cm.designation 
            FROM club_memberships cm 
            JOIN users u ON cm.user_id = u.id 
            WHERE cm.club_id = ? AND cm.designation = 'moderator'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: ['moderator_name' => '', 'designation' => ''];
}

function getDeanData($conn, $club_id)
{
    $sql = "SELECT u.full_name AS dean_name
            FROM club_memberships cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.club_id = ? AND cm.designation = 'dean'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: ['dean_name' => ''];
}

function getFacilitiesWithRooms($conn)
{
    $facilities = [];
    $sql = "SELECT f.id, f.name, r.id AS room_id, r.room_number, r.capacity, r.description 
            FROM facilities f 
            LEFT JOIN rooms r ON f.id = r.facility_id
            ORDER BY f.name, r.room_number";

    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            if (!isset($facilities[$row['id']])) {
                $facilities[$row['id']] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'rooms' => []
                ];
            }
            if ($row['room_id']) {
                $facilities[$row['id']]['rooms'][] = [
                    'id' => $row['room_id'],
                    'room_number' => $row['room_number'],
                    'capacity' => $row['capacity'],
                    'description' => $row['description']
                ];
            }
        }
        $result->free();
    }
    return $facilities;
}

function checkBookingConflict($conn, $facility_id, $room_id, $booking_date, $start_time, $end_time)
{
    // Debug log
    error_log("Checking conflicts for: Facility $facility_id, Room $room_id, Date $booking_date, Time $start_time - $end_time");

    $sql = "SELECT b.*, 
            TIME_FORMAT(b.start_time, '%l:%i %p') as formatted_start,
            TIME_FORMAT(b.end_time, '%l:%i %p') as formatted_end,
            f.name as facility_name,
            r.room_number,
            b.status
            FROM bookings b
            LEFT JOIN booking_rooms br ON b.id = br.booking_id
            LEFT JOIN facilities f ON b.facility_id = f.id
            LEFT JOIN rooms r ON br.room_id = r.id
            WHERE b.facility_id = ? 
            AND b.booking_date = ?
            AND b.status IN ('Pending', 'Confirmed')
            AND (
                (? BETWEEN b.start_time AND b.end_time)
                OR (? BETWEEN b.start_time AND b.end_time)
                OR (b.start_time BETWEEN ? AND ?)
                OR (b.end_time BETWEEN ? AND ?)
            )";

    if ($room_id) {
        $sql .= " AND (br.room_id = ? OR br.room_id IS NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issssssssi",
            $facility_id,
            $booking_date,
            $start_time,
            $end_time,
            $start_time,
            $end_time,
            $start_time,
            $end_time,
            $room_id
        );
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssss",
            $facility_id,
            $booking_date,
            $start_time,
            $end_time,
            $start_time,
            $end_time,
            $start_time,
            $end_time
        );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $conflicts = $result->fetch_all(MYSQLI_ASSOC);

    // Debug log
    error_log("Found " . count($conflicts) . " conflicts");
    foreach ($conflicts as $conflict) {
        error_log("Conflict: " . json_encode($conflict));
    }

    $stmt->close();
    return $conflicts;
}

function getExistingBookings($conn, $facility_id, $date)
{
    $sql = "SELECT b.*, 
            br.room_id, 
            r.room_number, 
            r.description,
            TIME_FORMAT(b.start_time, '%l:%i %p') as formatted_start,
            TIME_FORMAT(b.end_time, '%l:%i %p') as formatted_end,
            b.status,
            f.name as facility_name
            FROM bookings b
            LEFT JOIN booking_rooms br ON b.id = br.booking_id
            LEFT JOIN rooms r ON br.room_id = r.id
            LEFT JOIN facilities f ON b.facility_id = f.id
            WHERE b.facility_id = ? 
            AND b.booking_date = ?
            AND b.status IN ('Pending', 'Confirmed')
            ORDER BY b.start_time";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $facility_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    error_log("Existing bookings for facility $facility_id on $date: " . json_encode($bookings));
    return $bookings;
}

function findNextAvailableSlot($conn, $facility_id, $room_id, $desired_date, $desired_start, $desired_end)
{
    // Add a buffer time in minutes between bookings
    $buffer_minutes = 30; // You can adjust this value as needed

    $sql = "SELECT b.start_time, b.end_time 
            FROM bookings b
            LEFT JOIN booking_rooms br ON b.id = br.booking_id
            WHERE b.facility_id = ? 
            AND b.booking_date = ?
            AND b.status != 'Cancelled'
            " . ($room_id ? "AND br.room_id = ?" : "") . "
            ORDER BY b.start_time";

    $stmt = $conn->prepare($sql);
    if ($room_id) {
        $stmt->bind_param("isi", $facility_id, $desired_date, $room_id);
    } else {
        $stmt->bind_param("is", $facility_id, $desired_date);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);

    // Convert desired times to minutes for easier comparison
    $desired_duration = (strtotime($desired_end) - strtotime($desired_start)) / 60;
    $day_start = strtotime("08:00:00"); // Facility opens at 8 AM
    $day_end = strtotime("17:00:00");   // Facility closes at 5 PM

    $available_slots = [];
    $current_time = $day_start;

    foreach ($bookings as $booking) {
        $booking_start = strtotime($booking['start_time']);

        // Add buffer time to the previous booking's end time
        $current_time_with_buffer = $current_time + ($buffer_minutes * 60);

        $gap = ($booking_start - $current_time_with_buffer) / 60;

        if ($gap >= $desired_duration) {
            $slot_start_time = $current_time_with_buffer;
            $slot_end_time = $slot_start_time + ($desired_duration * 60);

            $available_slots[] = [
                'date' => $desired_date,
                'start' => date('h:i A', $slot_start_time),
                'end' => date('h:i A', $slot_end_time),
                'start_24' => date('H:i:s', $slot_start_time),
                'end_24' => date('H:i:s', $slot_end_time)
            ];
        }
        $current_time = strtotime($booking['end_time']);
    }

    // Check final gap of the day (with buffer)
    $final_start_time = $current_time + ($buffer_minutes * 60);
    $gap = ($day_end - $final_start_time) / 60;

    if ($gap >= $desired_duration) {
        $available_slots[] = [
            'date' => $desired_date,
            'start' => date('h:i A', $final_start_time),
            'end' => date('h:i A', $final_start_time + ($desired_duration * 60)),
            'start_24' => date('H:i:s', $final_start_time),
            'end_24' => date('H:i:s', $final_start_time + ($desired_duration * 60))
        ];
    }

    // If no slots found on same day, look for next 7 days with buffer
    if (empty($available_slots)) {
        for ($i = 1; $i <= 7; $i++) {
            $next_date = date('Y-m-d', strtotime($desired_date . " +$i days"));
            $stmt->execute([$facility_id, $next_date]);
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $start_time = strtotime('08:00:00');
                $end_time = $start_time + ($desired_duration * 60);
                $available_slots[] = [
                    'date' => $next_date,
                    'start' => date('h:i A', $start_time),
                    'end' => date('h:i A', $end_time),
                    'start_24' => date('H:i:s', $start_time),
                    'end_24' => date('H:i:s', $end_time)
                ];
                break;
            }
        }
    }

    return $available_slots;
}

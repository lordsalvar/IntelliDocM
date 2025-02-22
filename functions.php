<?php
// functions.php

// Sanitize and format form values
function setValue($data)
{
    return isset($data) && !empty($data) ? htmlspecialchars($data) : '';
}

// Set readonly for inputs
function setReadonly($data)
{
    return isset($data) && !empty($data) ? 'readonly' : '';
}

function sanitize_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Data-fetching functions

// Fetch club details for a user
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

// Fetch applicant details
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




// Fetch facilities and rooms
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
            if (!empty($row['room_id'])) {
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

function checkRoomBookingConflict($conn, $facility_id, $room_id, $booking_date, $start_time, $end_time)
{
    if (!isValidBookingTime($start_time) || !isValidBookingTime($end_time)) {
        return [
            [
                'conflict_type' => 'restricted_hours',
                'message' => 'Bookings are not allowed between 12 AM and 2:59 AM'
            ]
        ];
    }

    $start_time_24 = date('H:i:s', strtotime($start_time));
    $end_time_24 = date('H:i:s', strtotime($end_time));

    // Modified facility-wide booking check
    $facility_sql = "
        SELECT b.*, 
            TIME_FORMAT(b.start_time, '%l:%i %p') as formatted_start,
            TIME_FORMAT(b.end_time, '%l:%i %p') as formatted_end,
            f.name as facility_name,
            'facility' as booking_type
        FROM bookings b
        INNER JOIN facilities f ON b.facility_id = f.id
        LEFT JOIN booking_rooms br ON b.id = br.booking_id
        WHERE b.facility_id = ?
        AND b.booking_date = ?
        AND b.status IN ('Pending', 'Confirmed')
        AND br.room_id IS NULL
        AND (
            (? < b.end_time AND ? > b.start_time)    -- Check for actual overlap
            AND NOT (? = b.end_time OR ? = b.start_time) -- Allow exact matching times
        )";

    $stmt = $conn->prepare($facility_sql);
    $stmt->bind_param(
        "isssss",
        $facility_id,
        $booking_date,
        $start_time_24,
        $end_time_24,
        $start_time_24,
        $end_time_24
    );

    $stmt->execute();
    $facility_conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Then check for room-specific conflicts
    $room_sql = "
        SELECT b.*, 
            TIME_FORMAT(b.start_time, '%l:%i %p') as formatted_start,
            TIME_FORMAT(b.end_time, '%l:%i %p') as formatted_end,
            f.name as facility_name,
            r.room_number,
            'room' as booking_type
        FROM bookings b
        INNER JOIN facilities f ON b.facility_id = f.id
        INNER JOIN booking_rooms br ON b.id = br.booking_id
        INNER JOIN rooms r ON br.room_id = r.id
        WHERE b.facility_id = ?
        AND br.room_id = ?
        AND b.booking_date = ?
        AND b.status IN ('Pending', 'Confirmed')
        AND (
            (? < b.end_time AND ? > b.start_time)    -- Check for actual overlap
            AND NOT (? = b.end_time OR ? = b.start_time) -- Allow exact matching times
        )";

    $stmt = $conn->prepare($room_sql);
    $stmt->bind_param(
        "iisssss",
        $facility_id,
        $room_id,
        $booking_date,
        $start_time_24,
        $end_time_24,
        $start_time_24,
        $end_time_24
    );

    $stmt->execute();
    $room_conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Combine and format conflicts with detailed messages
    $all_conflicts = [];

    foreach ($facility_conflicts as $conflict) {
        $all_conflicts[] = [
            'conflict_type' => 'facility_wide',
            'facility_name' => $conflict['facility_name'],
            'start_time' => $conflict['formatted_start'],
            'end_time' => $conflict['formatted_end'],
            'message' => "Facility-wide booking exists for {$conflict['facility_name']} from {$conflict['formatted_start']} to {$conflict['formatted_end']}"
        ];
    }

    foreach ($room_conflicts as $conflict) {
        $overlap_type = '';
        $conflict_start = strtotime($conflict['start_time']);
        $conflict_end = strtotime($conflict['end_time']);
        $new_start = strtotime($start_time_24);
        $new_end = strtotime($end_time_24);

        if ($new_start >= $conflict_start && $new_end <= $conflict_end) {
            $overlap_type = 'completely within';
        } elseif ($new_start <= $conflict_start && $new_end >= $conflict_end) {
            $overlap_type = 'completely overlaps';
        } elseif ($new_start < $conflict_end && $new_end > $conflict_end) {
            $overlap_type = 'overlaps end';
        } elseif ($new_start < $conflict_start && $new_end > $conflict_start) {
            $overlap_type = 'overlaps start';
        }

        $all_conflicts[] = [
            'conflict_type' => 'room_specific',
            'room_number' => $conflict['room_number'],
            'facility_name' => $conflict['facility_name'],
            'start_time' => $conflict['formatted_start'],
            'end_time' => $conflict['formatted_end'],
            'overlap_type' => $overlap_type,
            'message' => "Room {$conflict['room_number']} has a booking that {$overlap_type} from {$conflict['formatted_start']} to {$conflict['formatted_end']}"
        ];
    }

    return $all_conflicts;
}

function checkBookingConflict($conn, $facility_id, $room_id, $booking_date, $start_time, $end_time)
{
    $sql = "
        SELECT b.*, 
               TIME_FORMAT(b.start_time, '%h:%i %p') as formatted_start,
               TIME_FORMAT(b.end_time, '%h:%i %p') as formatted_end
        FROM bookings b
        WHERE b.facility_id = ? 
        AND b.booking_date = ?
        AND b.status IN ('Pending', 'Confirmed')
        AND (
            (? < b.end_time AND ? > b.start_time) -- Standard Overlap
            AND NOT (? = b.end_time OR ? = b.start_time) -- Allow Back-to-Back
        )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssss",
        $facility_id,
        $booking_date,
        $start_time,
        $end_time,
        $start_time,
        $end_time
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $conflicts = $result->fetch_all(MYSQLI_ASSOC);
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
    $desired_duration = (strtotime($desired_end) - strtotime($desired_start)) / 60; // in minutes
    $day_start = strtotime("08:00:00");
    $day_end = strtotime("23:00:00"); // No bookings past 11 PM

    $sql = "SELECT b.start_time, b.end_time 
            FROM bookings b
            WHERE b.facility_id = ? 
            AND b.booking_date = ?
            AND b.status IN ('Pending', 'Confirmed')
            ORDER BY b.start_time";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $facility_id, $desired_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $current_time = $day_start;
    $available_slots = [];

    foreach ($bookings as $booking) {
        $booking_start = strtotime($booking['start_time']);
        $gap = ($booking_start - $current_time) / 60;

        if ($gap >= $desired_duration) {
            $available_slots[] = [
                'date' => $desired_date,
                'start' => date('h:i A', $current_time),
                'end' => date('h:i A', $current_time + ($desired_duration * 60)),
                'start_24' => date('H:i:s', $current_time),
                'end_24' => date('H:i:s', $current_time + ($desired_duration * 60))
            ];
        }
        $current_time = strtotime($booking['end_time']);
    }

    if (($day_end - $current_time) / 60 >= $desired_duration) {
        $available_slots[] = [
            'date' => $desired_date,
            'start' => date('h:i A', $current_time),
            'end' => date('h:i A', $current_time + ($desired_duration * 60)),
            'start_24' => date('H:i:s', $current_time),
            'end_24' => date('H:i:s', $current_time + ($desired_duration * 60))
        ];
    }

    // If no slots found, check next available day
    if (empty($available_slots)) {
        for ($i = 1; $i <= 7; $i++) {
            $next_date = date('Y-m-d', strtotime($desired_date . " +$i days"));
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE facility_id = ? AND booking_date = ?");
            $stmt->bind_param("is", $facility_id, $next_date);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();

            if ($count === 0) { // If no bookings, suggest this date
                $available_slots[] = [
                    'date' => $next_date,
                    'start' => "08:00 AM",
                    'end' => "10:00 AM",
                    'start_24' => "08:00:00",
                    'end_24' => "10:00:00"
                ];
                break;
            }
        }
    }

    return $available_slots;
}


// Add this new function after the existing functions
// Check if a booking time is valid (prevents 12 AM - 2:59 AM)
function isValidBookingTime($time)
{
    $hour = (int)date('H', strtotime($time));
    return !($hour >= 0 && $hour < 3);
}

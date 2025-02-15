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
    // If room_id is provided, use the room-specific conflict checker
    if ($room_id) {
        return checkRoomBookingConflict($conn, $facility_id, $room_id, $booking_date, $start_time, $end_time);
    }

    // Add time validation
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

    // Modified SQL to allow back-to-back bookings
    $sql = "
        SELECT DISTINCT 
            b.*, 
            TIME_FORMAT(b.start_time, '%l:%i %p') as formatted_start,
            TIME_FORMAT(b.end_time, '%l:%i %p') as formatted_end,
            f.name as facility_name,
            r.room_number,
            r.id as room_id,
            b.status,
            CASE 
                WHEN br.room_id IS NULL THEN 'facility'
                ELSE 'room'
            END as booking_type
        FROM bookings b
        INNER JOIN facilities f ON b.facility_id = f.id
        LEFT JOIN booking_rooms br ON b.id = br.booking_id
        LEFT JOIN rooms r ON br.room_id = r.id
        WHERE b.facility_id = ?
        AND b.booking_date = ?
        AND b.status IN ('Pending', 'Confirmed')
        AND (
            (? < b.end_time AND ? > b.start_time)    -- Check for actual overlap
            AND NOT (? = b.end_time OR ? = b.start_time) -- Allow exact matching times
        )";

    $stmt = $conn->prepare($sql);
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
    $result = $stmt->get_result();
    $conflicts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Process and format conflicts with detailed overlap information
    $formatted_conflicts = [];
    foreach ($conflicts as $conflict) {
        $conflict_start = strtotime($conflict['start_time']);
        $conflict_end = strtotime($conflict['end_time']);
        $new_start = strtotime($start_time_24);
        $new_end = strtotime($end_time_24);

        // Determine overlap type
        $overlap_type = '';
        if ($new_start >= $conflict_start && $new_end <= $conflict_end) {
            $overlap_type = 'completely within';
        } elseif ($new_start <= $conflict_start && $new_end >= $conflict_end) {
            $overlap_type = 'completely overlaps';
        } elseif ($new_start < $conflict_end && $new_end > $conflict_end) {
            $overlap_type = 'overlaps end';
        } elseif ($new_start < $conflict_start && $new_end > $conflict_start) {
            $overlap_type = 'overlaps start';
        }

        // Format the conflict message based on booking type
        if ($conflict['booking_type'] === 'facility') {
            $formatted_conflicts[] = [
                'conflict_type' => 'facility_wide',
                'facility_name' => $conflict['facility_name'],
                'start_time' => $conflict['formatted_start'],
                'end_time' => $conflict['formatted_end'],
                'overlap_type' => $overlap_type,
                'message' => "Facility-wide booking {$overlap_type} for {$conflict['facility_name']} from {$conflict['formatted_start']} to {$conflict['formatted_end']}"
            ];
        } else {
            $formatted_conflicts[] = [
                'conflict_type' => 'room_specific',
                'room_number' => $conflict['room_number'],
                'facility_name' => $conflict['facility_name'],
                'start_time' => $conflict['formatted_start'],
                'end_time' => $conflict['formatted_end'],
                'overlap_type' => $overlap_type,
                'message' => "Room {$conflict['room_number']} has a booking that {$overlap_type} from {$conflict['formatted_start']} to {$conflict['formatted_end']}"
            ];
        }
    }

    return $formatted_conflicts;
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
    // Remove buffer_minutes variable since we don't need it anymore

    // Check if desired times are within restricted hours
    if (!isValidBookingTime($desired_start) || !isValidBookingTime($desired_end)) {
        // If trying to book during restricted hours, move to 8 AM of the same day
        // or next day if the current time is past 8 AM
        $current_hour = (int)date('H', strtotime($desired_start));
        if ($current_hour < 8) {
            $desired_start = "08:00:00";
        } else {
            $desired_date = date('Y-m-d', strtotime($desired_date . " +1 day"));
            $desired_start = "08:00:00";
        }
        $desired_end = date('H:i:s', strtotime($desired_start) + (strtotime($desired_end) - strtotime($desired_start)));
    }

    // Check for end time past 11:59 PM
    $desired_end_timestamp = strtotime($desired_end);
    $day_end_limit = strtotime("23:59:59");

    if ($desired_end_timestamp > $day_end_limit) {
        $desired_date = date('Y-m-d', strtotime($desired_date . " +1 day"));
        $desired_start = "08:00:00";
        $desired_end = date('H:i:s', strtotime($desired_start) + (strtotime($desired_end) - strtotime($desired_start)));
    }

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

    $desired_duration = (strtotime($desired_end) - strtotime($desired_start)) / 60;
    $day_start = strtotime("08:00:00");
    $day_end = strtotime("23:59:59"); // Updated to 11:59 PM

    $available_slots = [];
    $current_time = $day_start;

    foreach ($bookings as $booking) {
        $booking_start = strtotime($booking['start_time']);
        // Remove buffer time calculation
        $current_time_check = $current_time;

        // Additional check for restricted hours
        if (!isValidBookingTime(date('H:i:s', $current_time_check))) {
            $current_time_check = strtotime("08:00:00", strtotime("+1 day", $current_time_check));
        }

        $gap = ($booking_start - $current_time_check) / 60;

        // Check if the potential slot would end before 11:59 PM and not extend into restricted hours
        $potential_end_time = $current_time_check + ($desired_duration * 60);
        if (
            $gap >= $desired_duration &&
            $potential_end_time <= $day_end &&
            isValidBookingTime(date('H:i:s', $potential_end_time))
        ) {
            $available_slots[] = [
                'date' => $desired_date,
                'start' => date('h:i A', $current_time_check),
                'end' => date('h:i A', $potential_end_time),
                'start_24' => date('H:i:s', $current_time_check),
                'end_24' => date('H:i:s', $potential_end_time)
            ];
        }
        $current_time = strtotime($booking['end_time']);
    }

    // Check final gap of the day (without buffer)
    $final_start_time = $current_time;
    $final_end_time = $final_start_time + ($desired_duration * 60);
    $gap = ($day_end - $final_start_time) / 60;

    if ($gap >= $desired_duration && $final_end_time <= $day_end) {
        $available_slots[] = [
            'date' => $desired_date,
            'start' => date('h:i A', $final_start_time),
            'end' => date('h:i A', $final_end_time),
            'start_24' => date('H:i:s', $final_start_time),
            'end_24' => date('H:i:s', $final_end_time)
        ];
    }

    // If no slots found on same day or if end time would be past 11:59 PM, look for next 7 days
    if (empty($available_slots)) {
        for ($i = 1; $i <= 7; $i++) {
            $next_date = date('Y-m-d', strtotime($desired_date . " +$i days"));
            $stmt->execute([$facility_id, $next_date]);
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $start_time = strtotime('08:00:00');
                $end_time = $start_time + ($desired_duration * 60);

                // Only add slot if it ends before 11:59 PM
                if ($end_time <= strtotime("23:59:59")) {
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
    }

    return $available_slots;
}

// Add this new function after the existing functions
function isValidBookingTime($time)
{
    $hour = (int)date('H', strtotime($time));
    return !($hour >= 0 && $hour < 3); // Returns false for hours between 12 AM and 2:59 AM
}

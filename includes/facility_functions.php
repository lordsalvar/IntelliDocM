<?php

function getRoomCount($facilityId)
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM rooms WHERE facility_id = ?");
    $stmt->bind_param("i", $facilityId);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function getTotalCapacity($facilityId)
{
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(capacity) FROM rooms WHERE facility_id = ?");
    $stmt->bind_param("i", $facilityId);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0] ?? 0;
}

function getBookingStats($facilityId = null)
{
    global $conn;
    $where = $facilityId ? "WHERE facility_id = ?" : "";
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings " . $where;

    if ($facilityId) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facilityId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    return $conn->query($query)->fetch_assoc();
}

function formatBookingDate($date, $format = 'M d, Y')
{
    return date($format, strtotime($date));
}

function getStatusClass($status)
{
    return match (strtolower($status)) {
        'confirmed' => 'success',
        'cancelled' => 'danger',
        default => 'warning'
    };
}

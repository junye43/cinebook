<?php
/** Booking business rules. Pure functions so they can be tested without MySQL. */

function cb_post_text($key) {
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}

function cb_post_positive_int($key) {
    $value = cb_post_text($key);
    return preg_match('/^[1-9][0-9]{0,8}$/D', $value) ? (int) $value : 0;
}

function cb_validate_seats($raw, $numTickets) {
    $errors = [];
    if ($raw === '') {
        return ['seats' => [], 'errors' => ['Please select your seats.']];
    }
    if (strlen($raw) > 100) {
        return ['seats' => [], 'errors' => ['Seat selection is too long.']];
    }
    $parts = explode(',', $raw);
    if (count($parts) > 10) {
        return ['seats' => [], 'errors' => ['You can book a maximum of 10 seats.']];
    }
    $seats = [];
    foreach ($parts as $part) {
        $seat = strtoupper(trim($part));
        if (!preg_match('/^[A-H](?:10|[1-9])$/D', $seat)) {
            $errors[] = 'Seats must be between A1 and H10.';
            break;
        }
        if (in_array($seat, $seats, true)) {
            $errors[] = 'You cannot select the same seat twice.';
            break;
        }
        $seats[] = $seat;
    }
    if (!$errors && count($seats) !== $numTickets) {
        $errors[] = 'Ticket quantity must match the number of selected seats.';
    }
    return ['seats' => $seats, 'errors' => $errors];
}

function cb_showtime_is_future($date, $time, $now = null) {
    $tz = new DateTimeZone('Asia/Singapore');
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $date . ' ' . (string) $time, $tz);
    if (!$dt || $dt->format('Y-m-d H:i:s') !== (string) $date . ' ' . (string) $time) return false;
    return $dt > ($now ?: new DateTimeImmutable('now', $tz));
}

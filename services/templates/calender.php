<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$calendar_id = $_GET['calendar_id'] ?? null;
$title = $_GET['title'] ?? null;
$summary = $_GET['summary'] ?? null;
$description = $_GET['description'] ?? null;
$location = $_GET['location'] ?? null;
$email = $_GET['email'] ?? null;

if (!isset($_GET['calendar_id'])) {
    http_response_code(400);
    exit;
}

if (!isset($_GET['start']) || !isset($_GET['end']) || !isset($_GET['title']) || !isset($_GET['email'])) {
    http_response_code(400);
    exit;
}

// Validate dates
if (!strtotime($start) || !strtotime($end) || strtotime($start) > strtotime($end)) {
    http_response_code(400);
    exit;
}

// Format dates for iCalendar (YYYYMMDDTHHMMSSZ)
function formatICALDate($date) {
    return date('Ymd\THis\Z', strtotime($date));
}

$uid = md5(uniqid(mt_rand(), true)) . '@medhealth.com';
$timestamp = gmdate('Ymd\THis\Z');

$ical = "BEGIN:VCALENDAR\r\n" .
    "VERSION:2.0\r\n" .
    "PRODID:-//MedHealth//Calendar//EN\r\n" .
    "CALSCALE:GREGORIAN\r\n" .
    "METHOD:REQUEST\r\n" .
    "BEGIN:VEVENT\r\n" .
    "UID:" . $uid . "\r\n" .
    "DTSTAMP:" . $timestamp . "\r\n" .
    "DTSTART:" . formatICALDate($start) . "\r\n" .
    "DTEND:" . formatICALDate($end) . "\r\n" .
    "SUMMARY:" . addslashes($summary ?? $title) . "\r\n" .
    "DESCRIPTION:" . addslashes($description ?? '') . "\r\n" .
    "LOCATION:" . addslashes($location ?? '') . "\r\n" .
    "ORGANIZER:mailto:" . addslashes($email) . "\r\n" .
    "STATUS:CONFIRMED\r\n" .
    "END:VEVENT\r\n" .
    "END:VCALENDAR";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=appointment.ics');
echo $ical;
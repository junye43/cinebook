<?php
/* ============================================================
   JSON API — movies   (ADDITIONAL VERSION / modern enhancement)
   The React single-page app fetches this endpoint. It reads the
   SAME MySQL database used by the traditional PHP base version.
   AJAX + JSON are allowed in the Additional Version only.
   ============================================================ */

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

require_once __DIR__ . '/../includes/db_connect.php';

$sql = "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
               m.Description, m.Certificate, m.Stars, m.Status, m.Poster, m.Backdrop,
               c.Cinema_Name, b.Bran_Location
        FROM movie m
        LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
        LEFT JOIN branch b ON c.Bran_ID = b.Bran_ID
        ORDER BY m.Date ASC, m.Showtime ASC";

$result = $conn->query($sql);

$movies = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $movies[] = [
            "id"          => (int) $row["Movie_ID"],
            "title"       => $row["Title"],
            "genre"       => $row["Genre"],
            "duration"    => (int) $row["Duration_Min"],
            "date"        => $row["Date"],
            "showtime"    => $row["Showtime"],
            "description" => $row["Description"],
            "certificate" => $row["Certificate"],
            "stars"       => (int) $row["Stars"],
            "status"      => $row["Status"],
            "poster"      => $row["Poster"],
            "backdrop"    => $row["Backdrop"],
            "cinema"      => $row["Cinema_Name"],
            "branch"      => $row["Bran_Location"],
        ];
    }
}

echo json_encode([
    "status" => "ok",
    "count"  => count($movies),
    "movies" => $movies,
]);

$conn->close();

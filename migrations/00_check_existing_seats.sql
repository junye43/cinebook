-- CineBook: run BEFORE the migration in phpMyAdmin on cinebook_db.
-- Both queries MUST return zero rows. Fix old data first if they do not.

-- 1) Invalid legacy seat lists, mismatched ticket counts, missing movie references.
SELECT Res_Code, Movie_ID, Seats, Num_Tickets
FROM reservation
WHERE Movie_ID IS NULL
   OR Seats IS NULL
   OR UPPER(REPLACE(Seats, ' ', '')) NOT REGEXP '^([A-H](10|[1-9]))(,[A-H](10|[1-9]))*$'
   OR Num_Tickets <> 1 + LENGTH(Seats) - LENGTH(REPLACE(Seats, ',', ''))
   OR Num_Tickets NOT BETWEEN 1 AND 10;

-- 2) Any seat already recorded for multiple reservations of the SAME showtime,
--    including duplicates within one reservation. Resolve conflicts BEFORE migrating.
SELECT r.Movie_ID, UPPER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(r.Seats,' ',''), ',', numbers.n), ',', -1))) AS Seat_Code,
       COUNT(*) AS times_booked,
       GROUP_CONCAT(r.Res_Code ORDER BY r.Res_Code) AS reservation_ids
FROM reservation r
CROSS JOIN (
 SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) numbers
WHERE numbers.n <= 1 + LENGTH(r.Seats) - LENGTH(REPLACE(r.Seats, ',', ''))
GROUP BY r.Movie_ID, Seat_Code
HAVING COUNT(*) > 1;

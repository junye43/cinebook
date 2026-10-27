-- CineBook safe existing-database migration (MariaDB 10.4+, MySQL 5.7+).
-- BACK UP using phpMyAdmin Export first. Never import database.sql here.
-- Run migrations/00_check_existing_seats.sql and resolve ANY returned rows FIRST.
-- No DROP/DELETE/TRUNCATE operations in this script.
-- Can be re-run: prior migrated seats are skipped, but contradictory reservations
-- will cause a UNIQUE constraint error rather than being silently discarded.

CREATE TABLE IF NOT EXISTS reservation_seat (
    Res_Code INT NOT NULL,
    Movie_ID INT NOT NULL,
    Seat_Code VARCHAR(3) NOT NULL,
    PRIMARY KEY (Movie_ID, Seat_Code),
    UNIQUE KEY uniq_reservation_seat (Res_Code, Seat_Code),
    CONSTRAINT fk_cb_seat_res FOREIGN KEY (Res_Code) REFERENCES reservation(Res_Code) ON DELETE CASCADE,
    CONSTRAINT fk_cb_seat_movie FOREIGN KEY (Movie_ID) REFERENCES movie(Movie_ID),
    CONSTRAINT chk_cb_seat_code CHECK (Seat_Code REGEXP '^[A-H](10|[1-9])$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill every existing reservation's seat assignments. This works with
-- rows that store "A1, A2" or "A1,A2" and a maximum of 10 seats.
-- UNIQUE (Movie_ID, Seat_Code) will STOP the import if any seat is double booked.
INSERT INTO reservation_seat (Res_Code, Movie_ID, Seat_Code)
SELECT r.Res_Code, r.Movie_ID,
       UPPER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(r.Seats,' ',''), ',', numbers.n), ',', -1))) AS Seat_Code
FROM reservation r
CROSS JOIN (
 SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) numbers
WHERE r.Movie_ID IS NOT NULL
  AND numbers.n <= 1 + LENGTH(r.Seats) - LENGTH(REPLACE(r.Seats, ',', ''))
  AND NOT EXISTS (
      SELECT 1 FROM reservation_seat existing
      WHERE existing.Res_Code = r.Res_Code
        AND existing.Seat_Code = UPPER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(r.Seats,' ',''), ',', numbers.n), ',', -1)))
  );

-- Verify populated seat count. Expected: zero missing seats.
SELECT
  (SELECT COALESCE(SUM(Num_Tickets),0) FROM reservation) AS legacy_ticket_count,
  (SELECT COUNT(*) FROM reservation_seat) AS protected_seat_count;

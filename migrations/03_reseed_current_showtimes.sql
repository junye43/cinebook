-- ============================================================
-- CineBook — reseed showtimes to the CURRENT week, multi-outlet
-- ------------------------------------------------------------
-- WHY: demo showtimes had fixed past dates, so nothing appeared as
-- "now showing". This reseeds every now-showing film to play at BOTH
-- outlets (each in its own hall) across the next 5 days, dated
-- relative to CURDATE() so they never expire.
--
-- SAFE: keeps customer accounts, cinemas, branches and payment types.
-- It DOES clear existing bookings (reservations/transactions) because
-- they point at the old showtime rows being replaced.
--
-- Run:  mysql -uroot cinebook_db < migrations/03_reseed_current_showtimes.sql
-- ============================================================
USE cinebook_db;

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM transaction;
DELETE FROM reservation_seat;
DELETE FROM reservation;
DELETE FROM movie;
SET FOREIGN_KEY_CHECKS = 1;
ALTER TABLE movie AUTO_INCREMENT = 1;

-- ----- NOW SHOWING ------------------------------------------
-- Generated set = film  ×  outlet  ×  day (today..+4)  ×  time.
-- Each film sits in its own hall (Hall <idx>) at every outlet, so a
-- single outlet runs several films and a single film runs at several
-- outlets — the showtime already fixes the outlet + hall.
INSERT INTO movie (Location, Date, Showtime, Description, Title, Poster, Backdrop, Genre, Duration_Min, Certificate, Stars, Status, Cinema_ID)
SELECT CONCAT('Hall ', ((cat.idx - 1 + d.n + CASE WHEN loc.cid = 1 THEN 0 ELSE 3 END) % 6) + 1),
       DATE_ADD(CURDATE(), INTERVAL d.n DAY),
       t.tm,
       cat.descr, cat.title, cat.poster, cat.backdrop, cat.genre,
       cat.dur, cat.cert, cat.stars, 'showing', loc.cid
FROM (
    SELECT 1 AS idx, 'Stellar Horizon'     AS title, 'stellarhorizon.png'    AS poster, 'stellarhorizonposter.png'    AS backdrop, 'Sci-Fi'  AS genre, 128 AS dur, 'PG13' AS cert, 5 AS stars, 'A brilliant neurosurgeon is drawn into the world of the mystic arts on a journey of physical and spiritual healing, confronting a danger that threatens the entire multiverse.' AS descr
    UNION ALL SELECT 2, 'The Last Lighthouse', 'thelastlighthouse.png', 'thelastlighthouseposter.png', 'Drama',    105, 'PG',   4, 'A heartwarming story of friendship and courage set on a remote coast, following a keeper who guards more than just the light.'
    UNION ALL SELECT 3, 'Midnight Protocol',   'midnightprotocol.png',  'midnightprotocolposter.png',  'Action',   115, 'NC16', 3, 'Non-stop action as an elite team races against time to stop a global threat hidden in plain sight.'
    UNION ALL SELECT 4, 'Weekend Warriors',    'weekendwarriors.png',   'weekendwarriorsposter.png',   'Comedy',    98, 'PG',   4, 'A laugh-out-loud comedy about a chaotic family weekend that spirals hilariously out of control.'
    UNION ALL SELECT 5, 'Silent Echo',         'silentecho.png',        'silentechoposter.png',        'Thriller', 122, 'M18',  4, 'A gripping thriller where nothing is as it seems and every clue leads deeper into the dark.'
    UNION ALL SELECT 6, 'Painted Skies',       'paintedskies.png',      'paintedskiesposter.png',      'Family',    92, 'PG',   5, 'An animated adventure across enchanted lands, full of heart, wonder and unlikely heroes.'
) AS cat
CROSS JOIN (SELECT 1 AS cid UNION ALL SELECT 2) AS loc
CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) AS d
CROSS JOIN (SELECT '12:30:00' AS tm UNION ALL SELECT '16:00:00' UNION ALL SELECT '19:30:00') AS t;

-- ----- COMING SOON (fixed future release dates) --------------
INSERT INTO movie (Location, Date, Showtime, Description, Title, Poster, Backdrop, Genre, Duration_Min, Certificate, Stars, Status, Cinema_ID) VALUES
('Hall 1', '2027-01-15', '20:00:00', 'A kingdom rises and a hero is forged in this sweeping epic of loyalty, betrayal and destiny.', 'Crown of Ash',  'crownofash.png',  'crownofashposter.png', 'Drama',  134, 'PG13', 4, 'coming', 1),
('Hall 2', '2027-02-19', '21:00:00', 'When the city sleeps, one detective uncovers a conspiracy that reaches the highest towers.',    'Neon Alibi',    'neonalibi.png',   'neonalibiposter.png', 'Action', 118, 'NC16', 4, 'coming', 2),
('Hall 3', '2027-03-12', '17:30:00', 'A tiny hero with a big heart proves that size is never a limit when courage leads the way.',    'Pocket Dynamo', 'pocketdynamo.png','pocketdynamoposter.png', 'Family', 101, 'PG',   3, 'coming', 1),
('Hall 4', '2027-04-16', '19:45:00', 'Two rivals, one prize, and a race across the stars that will decide the fate of a galaxy.',    'Orbit Run',     'orbitrun.png',    'orbitrunposter.png', 'Sci-Fi', 126, 'PG13', 5, 'coming', 2);

-- ----- TRAILERS (hidden until the MP4 exists) ----------------
UPDATE movie SET Trailer_File = 'stellar_horizon.mp4'     WHERE Title = 'Stellar Horizon';
UPDATE movie SET Trailer_File = 'the_last_lighthouse.mp4' WHERE Title = 'The Last Lighthouse';
UPDATE movie SET Trailer_File = 'midnight_protocol.mp4'   WHERE Title = 'Midnight Protocol';
UPDATE movie SET Trailer_File = 'weekend_warriors.mp4'    WHERE Title = 'Weekend Warriors';
UPDATE movie SET Trailer_File = 'silent_echo.mp4'         WHERE Title = 'Silent Echo';
UPDATE movie SET Trailer_File = 'painted_skies.mp4'       WHERE Title = 'Painted Skies';
UPDATE movie SET Trailer_File = 'crown_of_ash.mp4'        WHERE Title = 'Crown of Ash';
UPDATE movie SET Trailer_File = 'neon_alibi.mp4'          WHERE Title = 'Neon Alibi';
UPDATE movie SET Trailer_File = 'pocket_dynamo.mp4'       WHERE Title = 'Pocket Dynamo';
UPDATE movie SET Trailer_File = 'orbit_run.mp4'           WHERE Title = 'Orbit Run';

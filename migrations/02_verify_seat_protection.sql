-- Both counts should match after backfill. Difference means do NOT deploy PHP updates yet.
SELECT
 (SELECT COALESCE(SUM(Num_Tickets),0) FROM reservation) AS legacy_ticket_count,
 (SELECT COUNT(*) FROM reservation_seat) AS protected_seat_count;
SHOW CREATE TABLE reservation_seat;

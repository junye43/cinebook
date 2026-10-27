"""Portable SQLite analogue (not a MySQL integration test) of the unique-key rule."""
import sqlite3
c = sqlite3.connect(":memory:")
c.execute("CREATE TABLE reservation_seat(Res_Code INTEGER, Movie_ID INTEGER, Seat_Code TEXT, PRIMARY KEY(Movie_ID,Seat_Code), UNIQUE(Res_Code,Seat_Code))")

def reserve(res, mid, seat):
    try:
        c.execute("INSERT INTO reservation_seat VALUES(?,?,?)", (res, mid, seat))
        return True
    except sqlite3.IntegrityError:
        return False
assert reserve(1, 4, "A1")
assert not reserve(2, 4, "A1"), "Same showtime and seat must conflict"
assert reserve(2, 5, "A1"), "Different showtime may reuse same seat code"
assert not reserve(1, 4, "A1"), "Duplicate assignment must conflict"
print("PASS: simulated seat unique-key constraints (not a live MySQL test)")

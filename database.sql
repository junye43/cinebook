-- ============================================================
-- CineBook Cinema Ticket Booking System
-- Database Schema (Base Version)
-- Import this via phpMyAdmin > Import, or run in the SQL tab.
-- Re-running this file safely drops and recreates everything.
-- ============================================================

CREATE DATABASE IF NOT EXISTS cinebook_db;
USE cinebook_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS transaction;
DROP TABLE IF EXISTS reservation;
DROP TABLE IF EXISTS payment_type;
DROP TABLE IF EXISTS customer;
DROP TABLE IF EXISTS movie;
DROP TABLE IF EXISTS cinema;
DROP TABLE IF EXISTS branch;
DROP TABLE IF EXISTS manager;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------
-- MANAGER
-- ---------------------------------------------------
CREATE TABLE manager (
    Manager_ID INT AUTO_INCREMENT PRIMARY KEY,
    Manager_Details VARCHAR(255) NOT NULL
);

-- ---------------------------------------------------
-- BRANCH  (has -> Manager)
-- ---------------------------------------------------
CREATE TABLE branch (
    Bran_ID INT AUTO_INCREMENT PRIMARY KEY,
    Bran_Location VARCHAR(150) NOT NULL,
    Manager_ID INT,
    FOREIGN KEY (Manager_ID) REFERENCES manager(Manager_ID)
);

-- ---------------------------------------------------
-- CINEMA (hos -> Branch)
-- ---------------------------------------------------
CREATE TABLE cinema (
    Cinema_ID INT AUTO_INCREMENT PRIMARY KEY,
    Cinema_Name VARCHAR(100) NOT NULL,
    Cinema_Cont VARCHAR(20),
    Bran_ID INT,
    FOREIGN KEY (Bran_ID) REFERENCES branch(Bran_ID)
);

-- ---------------------------------------------------
-- MOVIE (checks -> Cinema)
--   Certificate = film rating (e.g. PG13, NC16)
--   Stars       = editorial star rating 1-5
--   Status      = 'showing' or 'coming' (drives the home page sections)
-- ---------------------------------------------------
CREATE TABLE movie (
    Movie_ID INT AUTO_INCREMENT PRIMARY KEY,
    Location VARCHAR(150),
    Date DATE,
    Showtime TIME,
    Description TEXT,
    Title VARCHAR(150) NOT NULL,
    Poster VARCHAR(255) DEFAULT '',
    Genre VARCHAR(80),
    Duration_Min INT,
    Certificate VARCHAR(10) DEFAULT 'PG',
    Stars TINYINT DEFAULT 4,
    Status VARCHAR(20) DEFAULT 'showing',
    Cinema_ID INT,
    FOREIGN KEY (Cinema_ID) REFERENCES cinema(Cinema_ID)
);

-- ---------------------------------------------------
-- CUSTOMER
-- ---------------------------------------------------
CREATE TABLE customer (
    Cust_ID INT AUTO_INCREMENT PRIMARY KEY,
    Cust_Name VARCHAR(80) NOT NULL,
    Cust_Lname VARCHAR(80) NOT NULL,
    Cust_Age INT,
    Cust_Address VARCHAR(255),
    Cust_Number VARCHAR(20),
    Email VARCHAR(120) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL
);

-- ---------------------------------------------------
-- RESERVATION (confirms <- Customer, checks -> Movie)
-- ---------------------------------------------------
CREATE TABLE reservation (
    Res_Code INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Time TIME NOT NULL,
    Date DATE NOT NULL,
    Cont_Num VARCHAR(20),
    Seats VARCHAR(50) NOT NULL,
    Num_Tickets INT NOT NULL DEFAULT 1,
    Cust_ID INT,
    Movie_ID INT,
    FOREIGN KEY (Cust_ID) REFERENCES customer(Cust_ID),
    FOREIGN KEY (Movie_ID) REFERENCES movie(Movie_ID)
);

-- ---------------------------------------------------
-- PAYMENT_TYPE
-- ---------------------------------------------------
CREATE TABLE payment_type (
    Payment_Type_ID INT AUTO_INCREMENT PRIMARY KEY,
    Payment_Type VARCHAR(50) NOT NULL
);

-- ---------------------------------------------------
-- TRANSACTION (has -> Payment_type, cust_id, Res_ID)
-- ---------------------------------------------------
CREATE TABLE transaction (
    Trans_No INT AUTO_INCREMENT PRIMARY KEY,
    Cust_ID INT,
    Res_ID INT,
    Trans_Date DATE NOT NULL,
    Start_Date DATE,
    End_Date DATE,
    Total_Payment DECIMAL(8,2) NOT NULL,
    Payment_Type_ID INT,
    FOREIGN KEY (Cust_ID) REFERENCES customer(Cust_ID),
    FOREIGN KEY (Res_ID) REFERENCES reservation(Res_Code),
    FOREIGN KEY (Payment_Type_ID) REFERENCES payment_type(Payment_Type_ID)
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO manager (Manager_Details) VALUES
('Tan Wei Ming - Regional Manager'),
('Sarah Lim - Branch Manager');

INSERT INTO branch (Bran_Location, Manager_ID) VALUES
('Jurong Point', 1),
('Orchard Road', 2);

INSERT INTO cinema (Cinema_Name, Cinema_Cont, Bran_ID) VALUES
('Silver Village Jurong', '65001234', 1),
('Silver Village Orchard', '65005678', 2);

-- Now Showing
INSERT INTO movie (Location, Date, Showtime, Description, Title, Poster, Genre, Duration_Min, Certificate, Stars, Status, Cinema_ID) VALUES
('Hall 1', '2026-09-20', '14:30:00', 'A brilliant neurosurgeon is drawn into the world of the mystic arts on a journey of physical and spiritual healing, confronting a danger that threatens the entire multiverse.', 'Stellar Horizon', 'stellarhorizon.png', 'Sci-Fi', 128, 'PG13', 5, 'showing', 1),
('Hall 2', '2026-09-20', '18:00:00', 'A heartwarming story of friendship and courage set on a remote coast, following a keeper who guards more than just the light.', 'The Last Lighthouse', 'thelastlighthouse.png', 'Drama', 105, 'PG', 4, 'showing', 1),
('Hall 1', '2026-09-21', '20:15:00', 'Non-stop action as an elite team races against time to stop a global threat hidden in plain sight.', 'Midnight Protocol', 'midnightprotocol.png', 'Action', 115, 'NC16', 3, 'showing', 2),
('Hall 3', '2026-09-21', '16:00:00', 'A laugh-out-loud comedy about a chaotic family weekend that spirals hilariously out of control.', 'Weekend Warriors', 'weekendwarriors.png', 'Comedy', 98, 'PG', 4, 'showing', 2),
('Hall 2', '2026-09-22', '19:30:00', 'A gripping thriller where nothing is as it seems and every clue leads deeper into the dark.', 'Silent Echo', 'silentecho.png', 'Action', 122, 'M18', 4, 'showing', 1),
('Hall 4', '2026-09-22', '15:15:00', 'An animated adventure across enchanted lands, full of heart, wonder and unlikely heroes.', 'Painted Skies', 'paintedskies.png', 'Comedy', 92, 'PG', 5, 'showing', 2);

-- Coming Soon
INSERT INTO movie (Location, Date, Showtime, Description, Title, Poster, Genre, Duration_Min, Certificate, Stars, Status, Cinema_ID) VALUES
('Hall 1', '2026-10-10', '20:00:00', 'A kingdom rises and a hero is forged in this sweeping epic of loyalty, betrayal and destiny.', 'Crown of Ash', 'crownofash.png', 'Drama', 134, 'PG13', 4, 'coming', 1),
('Hall 2', '2026-10-18', '21:00:00', 'When the city sleeps, one detective uncovers a conspiracy that reaches the highest towers.', 'Neon Alibi', 'neonalibi.png', 'Action', 118, 'NC16', 4, 'coming', 2),
('Hall 3', '2026-11-01', '17:30:00', 'A tiny hero with a big heart proves that size is never a limit when courage leads the way.', 'Pocket Dynamo', 'pocketdynamo.png', 'Comedy', 101, 'PG', 3, 'coming', 1),
('Hall 4', '2026-11-14', '19:45:00', 'Two rivals, one prize, and a race across the stars that will decide the fate of a galaxy.', 'Orbit Run', 'orbitrun.png', 'Sci-Fi', 126, 'PG13', 5, 'coming', 2);

INSERT INTO payment_type (Payment_Type) VALUES
('Credit Card'), ('Debit Card'), ('PayNow');

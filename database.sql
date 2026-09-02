-- ============================================================
-- CineBook Cinema Ticket Booking System
-- Database Schema (matches ER Diagram)
-- Import this via phpMyAdmin > Import, or run in SQL tab
-- ============================================================

CREATE DATABASE IF NOT EXISTS cinebook_db;
USE cinebook_db;

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
-- ---------------------------------------------------
CREATE TABLE movie (
    Movie_ID INT AUTO_INCREMENT PRIMARY KEY,
    Location VARCHAR(150),
    Date DATE,
    Showtime TIME,
    Description TEXT,
    Title VARCHAR(150) NOT NULL,
    Poster VARCHAR(255) DEFAULT 'default_movie.jpg',
    Genre VARCHAR(80),
    Duration_Min INT,
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

INSERT INTO movie (Location, Date, Showtime, Description, Title, Genre, Duration_Min, Cinema_ID) VALUES
('Hall 1', '2026-09-05', '14:30:00', 'A thrilling sci-fi adventure across galaxies.', 'Stellar Horizon', 'Sci-Fi', 128, 1),
('Hall 2', '2026-09-05', '18:00:00', 'A heartwarming story of friendship and courage.', 'The Last Lighthouse', 'Drama', 105, 1),
('Hall 1', '2026-09-06', '20:15:00', 'Non-stop action as a team races against time.', 'Midnight Protocol', 'Action', 115, 2),
('Hall 3', '2026-09-06', '16:00:00', 'A laugh-out-loud comedy about family chaos.', 'Weekend Warriors', 'Comedy', 98, 2);

INSERT INTO payment_type (Payment_Type) VALUES
('Credit Card'), ('Debit Card'), ('PayNow');

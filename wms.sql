-- wms.sql - EcoTrack: Municipal Waste Complaint & Resolution System Database Schema

-- 1. Create Users Table (Citizens)
CREATE TABLE users (
    user_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- To store hashed password
    area VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Create Admin Table (Municipal Officials)
CREATE TABLE admin (
    admin_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- To store hashed password
    name VARCHAR(100) NOT NULL
);

-- 3. Create Complaints Table (UPDATED to include resolution_date)
CREATE TABLE complaints (
    complaint_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    complaint_title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NULL, 
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending', 
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_by_admin_id INT(11) NULL,
    -- *** NEW COLUMN ADDED HERE ***
    resolution_date TIMESTAMP NULL, 
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Insert a default Admin user (password: admin123)
-- The hashed value corresponds to the plain text 'admin123'
INSERT INTO admin (username, password, name) VALUES ('municipal_admin', '$2y$10$w09gP0dJ/e/M7k6p/H2nI.2LwXzT2D6nZq.YmG2lq7RkLh1M6jVpC', 'Chief Administrator');


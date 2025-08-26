CREATE DATABASE crud_system;
USE crud_system;

-- User table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user'
);

-- Data table (records for CRUD)
CREATE TABLE records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert an admin user (password = admin123 after hashing)
INSERT INTO users (username, password, role) 
VALUES ('admin', MD5('admin123'), 'admin');

--update users table column
ALTER TABLE users 
ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

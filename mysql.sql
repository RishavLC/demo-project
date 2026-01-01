CREATE DATABASE crud_system;
USE crud_system;

-- Roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE AFTER username;

-- Data table (records for CRUD)
CREATE TABLE records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert roles
INSERT INTO roles (role_name, description) VALUES
('admin', 'Full access to system'),
('user', 'Regular user with limited access');

-- Insert users
INSERT INTO users (username, password, role_id) VALUES
('admin', MD5('admin'), 1), -- admin
('user1', MD5('user2'), 2);    -- user

-- Auction items table
CREATE TABLE auction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,               -- user who listed the item
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    start_price DECIMAL(10,2) NOT NULL,
    current_price DECIMAL(10,2) DEFAULT 0.00,
    start_time DATETIME NULL,            
    end_time DATETIME NULL,               
    status ENUM('pending','upcoming','active','closed','sold','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);
ALTER TABLE auction_items 
ADD COLUMN winner_id INT NULL,
ADD FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE auction_items ADD COLUMN min_increment DECIMAL(10,2) DEFAULT 50;
ALTER TABLE auction_items ADD COLUMN buy_now_price DECIMAL(10,2) DEFAULT NULL;


-- bids
CREATE TABLE bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    bidder_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES auction_items(id) ON DELETE CASCADE,
    FOREIGN KEY (bidder_id) REFERENCES users(id) ON DELETE CASCADE
);

--auction result
CREATE TABLE auction_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    winner_id INT,
    winning_bid DECIMAL(10,2),
    closed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES auction_items(id),
    FOREIGN KEY (winner_id) REFERENCES users(id)
);

--notification table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE auction_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES auction_items(id) ON DELETE CASCADE
);

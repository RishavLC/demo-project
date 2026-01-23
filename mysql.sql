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
ALTER TABLE users
ADD COLUMN phone VARCHAR(20),
ADD COLUMN address VARCHAR(255),
ADD COLUMN citizenship_no VARCHAR(50),
ADD COLUMN nic_no VARCHAR(50);
ALTER TABLE users--for users status
ADD status ENUM('active','suspended','banned') DEFAULT 'active',
ALTER TABLE users ADD COLUMN suspended_at DATETIME NULL;
ALTER TABLE users
ADD photo VARCHAR(255) DEFAULT NULL;


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
    payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
    payment_deadline DATETIME,
    closed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES auction_items(id),
    FOREIGN KEY (winner_id) REFERENCES users(id)
);


--users penalty and for what reasons
CREATE TABLE user_penalties (
    penalty_id INT AUTO_INCREMENT PRIMARY KEY,
    id INT NOT NULL,
    reason VARCHAR(255),
    action_taken ENUM('warning','suspension','ban'),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    admin_note TEXT,
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

--second highest bidder as a backup
CREATE TABLE auction_backup_winners (
    backup_winner_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    id INT,
    bid_amount DECIMAL(10,2),
    priority INT, -- 1 = second bidder, 2 = third
    notified ENUM('yes','no') DEFAULT 'no',
    FOREIGN KEY (item_id) REFERENCES auction_items(id),
    FOREIGN KEY (id) REFERENCES users(id)
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
--feedback_table
CREATE TABLE auction_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open','reviewed') DEFAULT 'open',

    FOREIGN KEY (item_id) REFERENCES auction_items(id),
    FOREIGN KEY (sender_id) REFERENCES users(id)
);

CREATE TABLE auction_feedback_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_role ENUM('user','admin') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (feedback_id) REFERENCES auction_feedback(id),
    FOREIGN KEY (sender_id) REFERENCES users(id)
);

--payment table 
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_id INT,
    amount DECIMAL(10,2),
    status ENUM('pending','success','failed') DEFAULT 'pending',
    ref_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    payer_name VARCHAR(100),
    remarks TEXT,
    voucher_no VARCHAR(50);
);
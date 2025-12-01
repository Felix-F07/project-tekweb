
DROP DATABASE IF EXISTS warnet_db;
CREATE DATABASE warnet_db;
USE warnet_db;


CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL, 
    billing_seconds INT DEFAULT 0, 
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);


CREATE TABLE pc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, 
    status ENUM('available', 'used', 'maintenance') DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE paket_billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,       
    price INT NOT NULL DEFAULT 0,      
    seconds INT NOT NULL DEFAULT 0,    
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL
);


CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pc_id INT NULL,      
    paket_id INT NULL,   
    billing_amount INT NOT NULL, 
    seconds_added INT DEFAULT 0, 
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pc_id) REFERENCES pc(id),
    FOREIGN KEY (paket_id) REFERENCES paket_billing(id)
);


INSERT INTO roles (name) VALUES ('Admin'), ('User');


INSERT INTO pc (name, status) VALUES 
('PC-01', 'available'),
('PC-02', 'used'),
('PC-03', 'maintenance');


INSERT INTO users (username, password, role_id, billing_seconds) VALUES 
('admin', 'admin123', 1, 0);


INSERT INTO users (username, password, role_id, billing_seconds) VALUES 
('Kenji', '123', 2, 3600);


INSERT INTO users (username, password, role_id, billing_seconds) VALUES 
('Felix', '123', 2, 0);


INSERT INTO transactions (user_id, pc_id, billing_amount) VALUES 
(2, 1, 5000);


INSERT INTO paket_billing (name, price, seconds, description) VALUES
('Paket 1 Jam', 5000, 3600, 'Tambahan 1 jam penggunaan'),
('Paket 2 Jam', 10000, 7200, 'Tambahan 2 jam penggunaan'),
('Paket 5 Jam', 20000, 18000, 'Tambahan 5 jam penggunaan');
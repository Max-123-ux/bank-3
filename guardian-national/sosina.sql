-- Database: sosina_db
CREATE DATABASE IF NOT EXISTS sosina_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sosina_db;

-- Table: savers
CREATE TABLE IF NOT EXISTS savers (
    sv_id INT AUTO_INCREMENT PRIMARY KEY,
    sv_username VARCHAR(50) UNIQUE NOT NULL,
    sv_password VARCHAR(255) NOT NULL,
    sv_national_id VARCHAR(20) UNIQUE NOT NULL,
    sv_full_name VARCHAR(100) NOT NULL,
    sv_savings DECIMAL(15,2) DEFAULT 0.00,
    sv_bond_balance DECIMAL(15,2) DEFAULT 0.00,
    sv_user_type ENUM('saver','govt_officer') DEFAULT 'saver',
    sv_login_attempts INT DEFAULT 0,
    sv_suspended TINYINT(1) DEFAULT 0,
    sv_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sv_officer_stamp VARCHAR(50) DEFAULT NULL
);

-- Table: passbook_entries
CREATE TABLE IF NOT EXISTS passbook_entries (
    pe_id INT AUTO_INCREMENT PRIMARY KEY,
    pe_saver_id INT NOT NULL,
    pe_serial_number VARCHAR(20) UNIQUE NOT NULL,
    pe_type ENUM('deposit','withdrawal','transfer_in','transfer_out','bond_purchase','bond_interest','account_open') NOT NULL,
    pe_amount DECIMAL(15,2) NOT NULL,
    pe_balance_after DECIMAL(15,2) NOT NULL,
    pe_bond_reference VARCHAR(50) DEFAULT NULL,
    pe_counterparty_id INT DEFAULT NULL,
    pe_officer_stamp VARCHAR(100) DEFAULT NULL,
    pe_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pe_saver_id) REFERENCES savers(sv_id),
    FOREIGN KEY (pe_counterparty_id) REFERENCES savers(sv_id)
);

-- Default Government Officer
INSERT INTO savers (sv_username, sv_password, sv_national_id, sv_full_name, sv_user_type)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GNS-OFF-001', 'Government Officer', 'govt_officer')
ON DUPLICATE KEY UPDATE sv_username=sv_username;
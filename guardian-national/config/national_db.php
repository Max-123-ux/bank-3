<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sosina_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session timeout (60 seconds)
define('SESSION_TIMEOUT', 60);

// Maximum login attempts before suspension
define('MAX_LOGIN_ATTEMPTS', 3);

// Bond interest rate
define('BOND_INTEREST_RATE', 5.0);

// Auto-install database
function autoInstallDatabase() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Create tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS savers (
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
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS passbook_entries (
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
        )");
        
        // Create default govt officer
        $stmt = $pdo->prepare("SELECT sv_id FROM savers WHERE sv_username = 'admin'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO savers (sv_username, sv_password, sv_national_id, sv_full_name, sv_user_type) 
                          VALUES ('admin', ?, 'GNS-OFF-001', 'Government Officer', 'govt_officer')")
                ->execute([$hashedPassword]);
        }
        
        return true;
    } catch(PDOException $e) {
        die("Database installation failed: " . $e->getMessage());
    }
}

// Get database connection
function getDB() {
    try {
        static $pdo = null;
        if ($pdo === null) {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return $pdo;
    } catch(PDOException $e) {
        // Auto-install if database doesn't exist
        autoInstallDatabase();
        return getDB();
    }
}

// Check session timeout
function checkSession() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: ../../portal/citizen-login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../portal/citizen-login.php");
        exit();
    }
    checkSession();
}

// Generate serial number
function generateSerialNumber() {
    return 'GNS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Add passbook entry
function addPassbookEntry($pdo, $saver_id, $type, $amount, $balance_after, $bond_reference = null, $counterparty_id = null) {
    $serial = generateSerialNumber();
    $officer_stamp = isset($_SESSION['sv_officer_stamp']) ? $_SESSION['sv_officer_stamp'] : 'System Generated';
    
    $stmt = $pdo->prepare("INSERT INTO passbook_entries (pe_saver_id, pe_serial_number, pe_type, pe_amount, pe_balance_after, pe_bond_reference, pe_counterparty_id, pe_officer_stamp) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$saver_id, $serial, $type, $amount, $balance_after, $bond_reference, $counterparty_id, $officer_stamp]);
    return $serial;
}
?>
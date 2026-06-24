<?php
/**
 * Guardian National Savings Bank - Database Reset Tool
 * This will reset the entire database and keep only the Government Officer (admin) account
 */

require_once 'config/national_db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm === 'RESET') {
        try {
            $pdo = getDB();
            
            // Drop and recreate tables
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("DROP TABLE IF EXISTS passbook_entries");
            $pdo->exec("DROP TABLE IF EXISTS savers");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Recreate tables
            $pdo->exec("CREATE TABLE savers (
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
            
            $pdo->exec("CREATE TABLE passbook_entries (
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
            
            // Create ONLY the admin account
            $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO savers (sv_username, sv_password, sv_national_id, sv_full_name, sv_user_type, sv_officer_stamp) 
                                  VALUES (?, ?, ?, ?, 'govt_officer', ?)");
            $stmt->execute(['admin', $hashedPassword, 'GNS-OFF-001', 'Government Officer', 'GNS-OFF-001']);
            
            $message = "Database reset successfully! Only the Government Officer account exists now.";
            $message .= "\n\nLogin credentials:\nUsername: admin\nPassword: admin123\nNational ID: GNS-OFF-001";
            
        } catch (PDOException $e) {
            $error = "Database reset failed: " . $e->getMessage();
        }
    } else {
        $error = "Please type RESET in the confirmation field to proceed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Reset - Guardian National Savings Bank</title>
    <link rel="stylesheet" href="assets/css/government.css">
    <style>
        .reset-container {
            max-width: 700px;
            margin: 50px auto;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 3px solid #ff6b6b;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
        }
        
        .warning-icon {
            font-size: 60px;
            color: #ff6b6b;
        }
        
        .reset-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            padding: 15px;
            width: 200px;
            border: 3px solid #ff6b6b;
            text-transform: uppercase;
        }
        
        .btn-reset {
            background: #ff6b6b;
            color: white;
            border: 3px solid #cc0000;
            padding: 15px 40px;
            font-size: 18px;
            cursor: pointer;
            font-family: 'Georgia', serif;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .btn-reset:hover {
            background: #cc0000;
        }
        
        .success-box {
            background: #d4edda;
            border: 3px solid #28a745;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
        }
        
        .credentials {
            background: #f8f9fa;
            border: 2px solid #6c757d;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="book-container">
            <div class="gov-header">
                <div class="coat-of-arms">⚠️</div>
                <h1>Database Reset Tool</h1>
                <h2>Guardian National Savings Bank</h2>
            </div>
            
            <div style="padding: 30px;">
                <?php if ($message): ?>
                    <div class="success-box">
                        <div style="font-size: 40px;">✅</div>
                        <h2 style="color: #28a745; margin: 20px 0;">Reset Complete</h2>
                        <p style="white-space: pre-line;"><?php echo htmlspecialchars($message); ?></p>
                        
                        <div class="credentials">
                            <strong>Default Admin Login:</strong><br>
                            Username: <strong>admin</strong><br>
                            Password: <strong>admin123</strong><br>
                            National ID: <strong>GNS-OFF-001</strong>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <a href="portal/citizen-login.php" class="btn btn-primary">Go to Login Page</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="warning-box">
                        <div class="warning-icon">⚠️</div>
                        <h2 style="color: #cc0000; margin: 20px 0;">WARNING!</h2>
                        <p style="font-size: 16px; line-height: 1.8;">
                            This action will <strong>permanently delete</strong> all data from the database:
                        </p>
                        <ul style="text-align: left; margin: 20px 40px; line-height: 2;">
                            <li>❌ All saver accounts will be deleted</li>
                            <li>❌ All passbook entries will be erased</li>
                            <li>❌ All transaction history will be removed</li>
                            <li>✅ Only the Government Officer (admin) will remain</li>
                        </ul>
                        <p style="color: #cc0000; font-weight: bold;">This action CANNOT be undone!</p>
                    </div>
                    
                    <form method="POST" style="text-align: center;">
                        <div class="form-group">
                            <label style="font-size: 16px; color: #cc0000;">
                                Type <strong>RESET</strong> to confirm:
                            </label>
                            <input 
                                type="text" 
                                name="confirm" 
                                class="reset-input" 
                                placeholder="RESET" 
                                autocomplete="off"
                                required
                            >
                        </div>
                        
                        <button type="submit" class="btn-reset">
                            ⚡ Reset Database
                        </button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="portal/citizen-login.php" class="btn btn-brass">← Cancel & Return to Login</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="gov-footer">
                © <?php echo date('Y'); ?> Guardian National Savings Bank - System Administration
            </div>
        </div>
    </div>
    
    <script>
        // Prevent accidental submission
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const confirmValue = document.querySelector('[name="confirm"]').value;
            if (confirmValue !== 'RESET') {
                e.preventDefault();
                alert('Please type RESET exactly to confirm the database reset.');
            } else {
                return confirm('FINAL WARNING: This will delete ALL data except the admin account. Continue?');
            }
        });
    </script>
</body>
</html>
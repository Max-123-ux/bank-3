<?php
require_once '../config/national_db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $national_id = trim($_POST['national_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($national_id) || empty($full_name)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!preg_match('/^GNS-\d{3}-\d{3}$/', $national_id)) {
        $error = 'National ID must be in format: GNS-XXX-XXX';
    } else {
        $pdo = getDB();
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT sv_id FROM savers WHERE sv_username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already exists.';
        } else {
            // Check if National ID exists
            $stmt = $pdo->prepare("SELECT sv_id FROM savers WHERE sv_national_id = ?");
            $stmt->execute([$national_id]);
            if ($stmt->fetch()) {
                $error = 'National ID already registered.';
            } else {
                // Create account
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO savers (sv_username, sv_password, sv_national_id, sv_full_name, sv_user_type, sv_savings, sv_officer_stamp) 
                                      VALUES (?, ?, ?, ?, 'saver', 0.00, 'Account Opening')");
                $stmt->execute([$username, $hashedPassword, $national_id, $full_name]);
                
                $saver_id = $pdo->lastInsertId();
                
                // Add passbook entry
                $serial = generateSerialNumber();
                $stmt = $pdo->prepare("INSERT INTO passbook_entries (pe_saver_id, pe_serial_number, pe_type, pe_amount, pe_balance_after, pe_officer_stamp) 
                                      VALUES (?, ?, 'account_open', 0.00, 0.00, 'System - Account Opening')");
                $stmt->execute([$saver_id, $serial]);
                
                $success = 'Savings account opened successfully! You can now login.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Savings Account - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Guardian National Savings Bank</h1>
            <h2>Open New Savings Account</h2>
        </div>
        
        <div class="book-pages">
            <div class="left-page">
                <div class="page-title">📋 Account Requirements</div>
                <div class="info-card">
                    <h3>Required Documents</h3>
                    <ul style="margin-left: 20px; line-height: 2;">
                        <li>National ID Card</li>
                        <li>Proof of Citizenship</li>
                        <li>Initial Deposit (optional)</li>
                    </ul>
                </div>
                
                <div class="certificate-box" style="margin-top: 20px;">
                    <p style="color: var(--forest-green);"><strong>National ID Format</strong></p>
                    <p style="font-size: 20px; font-family: monospace;">GNS-XXX-XXX</p>
                    <div class="official-stamp">OFFICIAL</div>
                </div>
            </div>
            
            <div class="right-page">
                <div class="page-title">📝 New Account Form</div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="citizen-login.php" class="btn btn-primary">Proceed to Login</a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="gov-form">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>National ID (GNS-XXX-XXX)</label>
                            <input type="text" name="national_id" class="form-control" placeholder="GNS-XXX-XXX" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Password (min. 6 characters)</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Open Savings Account</button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="citizen-login.php" style="color: var(--forest-green);">← Back to Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Official Government Institution
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
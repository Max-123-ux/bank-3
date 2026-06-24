<?php
require_once '../config/national_db.php';

$error = '';
$timeout = isset($_GET['timeout']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM savers WHERE sv_username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $user['sv_suspended']) {
            $error = 'Account suspended. Contact a Government Officer.';
        } elseif ($user && password_verify($password, $user['sv_password'])) {
            // Reset login attempts
            $pdo->prepare("UPDATE savers SET sv_login_attempts = 0 WHERE sv_id = ?")->execute([$user['sv_id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['sv_id'];
            $_SESSION['sv_username'] = $user['sv_username'];
            $_SESSION['sv_full_name'] = $user['sv_full_name'];
            $_SESSION['sv_user_type'] = $user['sv_user_type'];
            $_SESSION['sv_national_id'] = $user['sv_national_id'];
            $_SESSION['sv_officer_stamp'] = $user['sv_officer_stamp'] ?? 'Officer ' . $user['sv_national_id'];
            $_SESSION['last_activity'] = time();
            
            // Redirect based on user type
            if ($user['sv_user_type'] == 'govt_officer') {
                header("Location: ../officer/officer-desk.php");
            } else {
                header("Location: ../saver/savings-book.php");
            }
            exit();
        } else {
            // Increment login attempts
            if ($user) {
                $attempts = $user['sv_login_attempts'] + 1;
                $pdo->prepare("UPDATE savers SET sv_login_attempts = ? WHERE sv_id = ?")->execute([$attempts, $user['sv_id']]);
                
                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                    $pdo->prepare("UPDATE savers SET sv_suspended = 1 WHERE sv_id = ?")->execute([$user['sv_id']]);
                    $error = 'Account suspended after 3 failed attempts. Contact a Government Officer.';
                } else {
                    $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                    $error = "Invalid credentials. {$remaining} attempt(s) remaining.";
                }
            } else {
                $error = 'Invalid credentials.';
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
    <title>Guardian National Savings - Official Portal</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Guardian National Savings Bank</h1>
            <h2>Official Government Portal - Citizen Access</h2>
        </div>
        
        <div class="book-pages">
            <div class="left-page">
                <div class="page-title">📜 Official Notice</div>
                <div class="info-card">
                    <h3>Government Savings Scheme</h3>
                    <p>Access your Savings Book Balance, perform Counter Deposits & Withdrawals, purchase Government Bonds at 5% fixed interest, and transfer between accounts.</p>
                    <p style="margin-top: 10px;"><strong>National ID Format:</strong> GNS-XXX-XXX</p>
                </div>
                
                <div class="certificate-box" style="margin-top: 30px;">
                    <h3 style="color: var(--forest-green);">Government Bond</h3>
                    <p style="font-size: 24px; color: var(--brass);">5% Fixed Interest</p>
                    <p>Secure your future with government-backed bonds</p>
                    <div class="official-stamp">GUARANTEED</div>
                </div>
            </div>
            
            <div class="right-page">
                <div class="page-title">🔐 Citizen Login</div>
                
                <?php if ($timeout): ?>
                    <div class="alert alert-warning">Session expired due to inactivity. Please login again.</div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="gov-form">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Access Savings Book</button>
                </form>
                
                <div style="margin-top: 20px; text-align: center;">
                    <p>New citizen? <a href="open-savings.php" style="color: var(--forest-green);">Open a Savings Account</a></p>
                </div>
            </div>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Official Government Institution
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
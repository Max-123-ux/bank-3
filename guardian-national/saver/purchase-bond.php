<?php
require_once '../config/national_db.php';
requireLogin();

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT sv_savings, sv_bond_balance FROM savers WHERE sv_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        $error = 'Amount must be greater than zero.';
    } elseif ($amount > $user['sv_savings']) {
        $error = 'Insufficient Savings Book Balance.';
    } else {
        $pdo->beginTransaction();
        try {
            // Deduct from savings, add to bonds
            $stmt = $pdo->prepare("UPDATE savers SET sv_savings = sv_savings - ?, sv_bond_balance = sv_bond_balance + ? WHERE sv_id = ?");
            $stmt->execute([$amount, $amount, $user_id]);
            
            $new_savings = $user['sv_savings'] - $amount;
            $bond_ref = 'BOND-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            
            // Add passbook entries
            addPassbookEntry($pdo, $user_id, 'bond_purchase', $amount, $new_savings, $bond_ref);
            
            $pdo->commit();
            $success = "Bond purchase successful! Reference: {$bond_ref}";
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT sv_savings, sv_bond_balance FROM savers WHERE sv_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Transaction failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Government Bond - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Government Bond Purchase</h1>
            <h2><?php echo BOND_INTEREST_RATE; ?>% Fixed Interest - Government Guaranteed</h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div class="book-pages">
            <div class="left-page">
                <div class="page-title">🏛️ Bond Information</div>
                
                <div class="info-card">
                    <h3>Available Savings</h3>
                    <div class="balance-display currency-display"><?php echo number_format($user['sv_savings'], 2); ?></div>
                </div>
                
                <div class="info-card">
                    <h3>Current Bond Holdings</h3>
                    <div class="balance-display currency-display"><?php echo number_format($user['sv_bond_balance'], 2); ?></div>
                </div>
                
                <div class="projection-card">
                    <h3>Interest Projection</h3>
                    <p>Annual Interest: K <?php echo number_format($user['sv_bond_balance'] * (BOND_INTEREST_RATE / 100), 2); ?></p>
                    <div class="official-stamp"><?php echo BOND_INTEREST_RATE; ?>% FIXED</div>
                </div>
            </div>
            
            <div class="right-page">
                <div class="page-title">📝 Purchase Bonds</div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="gov-form">
                    <div class="form-group">
                        <label>Amount to Invest (K)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" max="<?php echo $user['sv_savings']; ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Purchase Government Bond</button>
                </form>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="savings-book.php" class="btn btn-brass">← Back to Savings Book</a>
                </div>
            </div>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Bonds guaranteed by the Government
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
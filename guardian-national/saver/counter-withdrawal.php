<?php
require_once '../config/national_db.php';
requireLogin();

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get current balance
$stmt = $pdo->prepare("SELECT sv_savings FROM savers WHERE sv_id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetch()['sv_savings'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        $error = 'Amount must be greater than zero.';
    } elseif ($amount > $balance) {
        $error = 'Insufficient funds in Savings Book Balance.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE savers SET sv_savings = sv_savings - ? WHERE sv_id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $new_balance = $balance - $amount;
            $serial = addPassbookEntry($pdo, $user_id, 'withdrawal', $amount, $new_balance);
            
            $pdo->commit();
            $success = "Counter Withdrawal successful! Serial: {$serial}";
            $balance = $new_balance;
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
    <title>Counter Withdrawal - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Counter Withdrawal</h1>
            <h2>Official Savings Transaction</h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div class="book-pages">
            <div class="left-page">
                <div class="page-title">💸 Withdrawal Information</div>
                <div class="info-card">
                    <h3>Available Balance</h3>
                    <div class="balance-display currency-display"><?php echo number_format($balance, 2); ?></div>
                </div>
            </div>
            
            <div class="right-page">
                <div class="page-title">📝 Make Withdrawal</div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <div class="certificate-box">
                        <h3>Withdrawal Receipt</h3>
                        <p>Amount: K <?php echo number_format($amount, 2); ?></p>
                        <p>Remaining Balance: K <?php echo number_format($balance, 2); ?></p>
                        <div class="official-stamp">WITHDRAWN</div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="gov-form">
                    <div class="form-group">
                        <label>Amount (K)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" max="<?php echo $balance; ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Process Counter Withdrawal</button>
                </form>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="savings-book.php" class="btn btn-brass">← Back to Savings Book</a>
                </div>
            </div>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
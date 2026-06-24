<?php
require_once '../config/national_db.php';
requireLogin();

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT sv_savings, sv_national_id FROM savers WHERE sv_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipient_id = trim($_POST['recipient_id'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    
    if (empty($recipient_id) || $amount <= 0) {
        $error = 'All fields are required.';
    } elseif ($amount > $user['sv_savings']) {
        $error = 'Insufficient Savings Book Balance.';
    } else {
        // Find recipient
        $stmt = $pdo->prepare("SELECT sv_id, sv_full_name FROM savers WHERE sv_national_id = ? AND sv_id != ? AND sv_user_type = 'saver'");
        $stmt->execute([$recipient_id, $user_id]);
        $recipient = $stmt->fetch();
        
        if (!$recipient) {
            $error = 'Recipient National ID not found.';
        } else {
            $pdo->beginTransaction();
            try {
                // Deduct from sender
                $stmt = $pdo->prepare("UPDATE savers SET sv_savings = sv_savings - ? WHERE sv_id = ?");
                $stmt->execute([$amount, $user_id]);
                
                // Add to recipient
                $stmt = $pdo->prepare("UPDATE savers SET sv_savings = sv_savings + ? WHERE sv_id = ?");
                $stmt->execute([$amount, $recipient['sv_id']]);
                
                // Get new balances
                $stmt = $pdo->prepare("SELECT sv_savings FROM savers WHERE sv_id = ?");
                $stmt->execute([$user_id]);
                $sender_balance = $stmt->fetch()['sv_savings'];
                
                $stmt = $pdo->prepare("SELECT sv_savings FROM savers WHERE sv_id = ?");
                $stmt->execute([$recipient['sv_id']]);
                $recipient_balance = $stmt->fetch()['sv_savings'];
                
                // Generate bond reference
                $bond_ref = 'TRF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
                
                // Add passbook entries for sender
                addPassbookEntry($pdo, $user_id, 'transfer_out', $amount, $sender_balance, $bond_ref, $recipient['sv_id']);
                
                // Add passbook entry for recipient
                addPassbookEntry($pdo, $recipient['sv_id'], 'transfer_in', $amount, $recipient_balance, $bond_ref, $user_id);
                
                $pdo->commit();
                $success = "Inter-Account Transfer successful! Reference: {$bond_ref}";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT sv_savings FROM savers WHERE sv_id = ?");
                $stmt->execute([$user_id]);
                $user['sv_savings'] = $stmt->fetch()['sv_savings'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Transfer failed. Please try again.';
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
    <title>Inter-Account Transfer - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Inter-Account Transfer</h1>
            <h2>Transfer Between Savings Accounts</h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div class="book-pages">
            <div class="left-page">
                <div class="page-title">🔄 Transfer Information</div>
                
                <div class="info-card">
                    <h3>Your Balance</h3>
                    <div class="balance-display currency-display"><?php echo number_format($user['sv_savings'], 2); ?></div>
                </div>
                
                <div class="info-card">
                    <h3>Your National ID</h3>
                    <p style="font-family: monospace; font-size: 16px;"><?php echo htmlspecialchars($user['sv_national_id']); ?></p>
                </div>
            </div>
            
            <div class="right-page">
                <div class="page-title">📝 Make Transfer</div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="gov-form">
                    <div class="form-group">
                        <label>Recipient National ID (GNS-XXX-XXX)</label>
                        <input type="text" name="recipient_id" class="form-control" placeholder="GNS-XXX-XXX" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount (K)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" max="<?php echo $user['sv_savings']; ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Process Inter-Account Transfer</button>
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
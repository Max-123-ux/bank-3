<?php
require_once '../config/national_db.php';
requireLogin();

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM savers WHERE sv_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get recent passbook entries
$stmt = $pdo->prepare("SELECT * FROM passbook_entries WHERE pe_saver_id = ? ORDER BY pe_created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_entries = $stmt->fetchAll();

// Calculate bond interest projection
$bond_interest = $user['sv_bond_balance'] * (BOND_INTEREST_RATE / 100);
$total_projection = $user['sv_savings'] + $user['sv_bond_balance'] + $bond_interest;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Book - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Savings Book</h1>
            <h2>Account Holder: <?php echo htmlspecialchars($user['sv_full_name']); ?></h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div class="book-pages">
            <!-- Left Page - Account Info & Actions -->
            <div class="left-page">
                <div class="page-title">📖 Account Information</div>
                
                <div class="info-card">
                    <h3>Account Holder</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['sv_full_name']); ?></p>
                    <p><strong>National ID:</strong> <?php echo htmlspecialchars($user['sv_national_id']); ?></p>
                    <p><strong>Account Status:</strong> 
                        <span style="color: <?php echo $user['sv_suspended'] ? 'red' : 'green'; ?>;">
                            <?php echo $user['sv_suspended'] ? 'Suspended' : 'Active'; ?>
                        </span>
                    </p>
                </div>
                
                <div class="certificate-box">
                    <h3 style="color: var(--forest-green);">Savings Book Balance</h3>
                    <div class="amount-display currency-display"><?php echo number_format($user['sv_savings'], 2); ?></div>
                    <div class="official-stamp">VERIFIED</div>
                </div>
                
                <div class="info-card" style="background: #f9f5e8;">
                    <h3>Government Bonds</h3>
                    <p><strong>Bond Balance:</strong> K <?php echo number_format($user['sv_bond_balance'], 2); ?></p>
                    <p><strong>Interest Rate:</strong> <?php echo BOND_INTEREST_RATE; ?>% Fixed</p>
                </div>
                
                <div class="nav-links">
                    <a href="counter-deposit.php" class="nav-link">💰 Counter Deposit</a>
                    <a href="counter-withdrawal.php" class="nav-link">💸 Counter Withdrawal</a>
                    <a href="purchase-bond.php" class="nav-link">🏛️ Bond Purchase</a>
                    <a href="inter-transfer.php" class="nav-link">🔄 Inter-Account Transfer</a>
                    <a href="passbook-history.php" class="nav-link">📜 Passbook History</a>
                    <a href="savings-certificate.php" class="nav-link">📄 Savings Certificate</a>
                    <a href="../portal/logout.php" class="nav-link" style="background: var(--danger);">🚪 Logout</a>
                </div>
            </div>
            
            <!-- Right Page - Recent Entries & Projections -->
            <div class="right-page">
                <div class="page-title">📊 Recent Passbook Entries</div>
                
                <?php if (count($recent_entries) > 0): ?>
                    <table class="passbook-table">
                        <thead>
                            <tr>
                                <th>Serial No.</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_entries as $entry): ?>
                                <tr>
                                    <td class="serial-number"><?php echo htmlspecialchars($entry['pe_serial_number']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $entry['pe_type'])); ?></td>
                                    <td>K <?php echo number_format($entry['pe_amount'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($entry['pe_created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-medium);">No passbook entries yet.</p>
                <?php endif; ?>
                
                <div class="projection-card" style="margin-top: 30px;">
                    <h3 style="color: var(--forest-green);">📈 Interest Projection Card</h3>
                    <p><strong>Current Savings:</strong> K <?php echo number_format($user['sv_savings'], 2); ?></p>
                    <p><strong>Bond Holdings:</strong> K <?php echo number_format($user['sv_bond_balance'], 2); ?></p>
                    <p><strong>Projected Bond Interest:</strong> K <?php echo number_format($bond_interest, 2); ?></p>
                    <div class="projection-amount">Total Projected: K <?php echo number_format($total_projection, 2); ?></div>
                    <div class="official-stamp">PROJECTED</div>
                </div>
            </div>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Savings Book No. <?php echo $user['sv_national_id']; ?>
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
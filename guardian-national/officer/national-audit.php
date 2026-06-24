<?php
require_once '../config/national_db.php';
requireLogin();

if ($_SESSION['sv_user_type'] != 'govt_officer') {
    header("Location: ../saver/savings-book.php");
    exit();
}

$pdo = getDB();

// Get all transactions
$transactions = $pdo->query("
    SELECT pe.*, s.sv_full_name, s.sv_national_id 
    FROM passbook_entries pe 
    JOIN savers s ON pe.pe_saver_id = s.sv_id 
    ORDER BY pe.pe_created_at DESC 
    LIMIT 100
")->fetchAll();

// Totals
$total_deposits = $pdo->query("SELECT SUM(pe_amount) FROM passbook_entries WHERE pe_type = 'deposit'")->fetchColumn();
$total_withdrawals = $pdo->query("SELECT SUM(pe_amount) FROM passbook_entries WHERE pe_type = 'withdrawal'")->fetchColumn();
$total_transfers = $pdo->query("SELECT SUM(pe_amount) FROM passbook_entries WHERE pe_type IN ('transfer_in', 'transfer_out')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>National Audit - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>National Audit Trail</h1>
            <h2>Complete Transaction History</h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div style="padding: 30px;">
            <a href="officer-desk.php" class="btn btn-brass" style="margin-bottom: 20px;">← Back to Officer Desk</a>
            
            <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
                <div class="info-card" style="flex: 1; min-width: 200px;">
                    <h4>Total Deposits</h4>
                    <p class="balance-display" style="font-size: 20px;">K <?php echo number_format($total_deposits ?? 0, 2); ?></p>
                </div>
                <div class="info-card" style="flex: 1; min-width: 200px;">
                    <h4>Total Withdrawals</h4>
                    <p class="balance-display" style="font-size: 20px;">K <?php echo number_format($total_withdrawals ?? 0, 2); ?></p>
                </div>
                <div class="info-card" style="flex: 1; min-width: 200px;">
                    <h4>Total Transfers</h4>
                    <p class="balance-display" style="font-size: 20px;">K <?php echo number_format($total_transfers ?? 0, 2); ?></p>
                </div>
            </div>
            
            <table class="passbook-table">
                <thead>
                    <tr>
                        <th>Serial No.</th>
                        <th>Date</th>
                        <th>Saver</th>
                        <th>National ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Officer Stamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td class="serial-number"><?php echo htmlspecialchars($t['pe_serial_number']); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($t['pe_created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($t['sv_full_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['sv_national_id']); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $t['pe_type'])); ?></td>
                            <td>K <?php echo number_format($t['pe_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($t['pe_officer_stamp']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Official Audit Record
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
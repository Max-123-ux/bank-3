<?php
require_once '../config/national_db.php';
requireLogin();

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Get all passbook entries
$stmt = $pdo->prepare("SELECT * FROM passbook_entries WHERE pe_saver_id = ? ORDER BY pe_created_at DESC");
$stmt->execute([$user_id]);
$entries = $stmt->fetchAll();

// Get user info
$stmt = $pdo->prepare("SELECT sv_full_name, sv_national_id FROM savers WHERE sv_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passbook History - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
    <style>
        @media print {
            body { background: white; }
            .book-container { box-shadow: none; border: 1px solid #000; }
            .nav-links, .btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Passbook History</h1>
            <h2><?php echo htmlspecialchars($user['sv_full_name']); ?> - <?php echo htmlspecialchars($user['sv_national_id']); ?></h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div style="padding: 30px;">
            <div style="text-align: right; margin-bottom: 20px;">
                <button onclick="window.print()" class="btn btn-brass">🖨️ Print Passbook</button>
                <a href="savings-book.php" class="btn btn-primary">← Back to Savings Book</a>
            </div>
            
            <?php if (count($entries) > 0): ?>
                <table class="passbook-table">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                            <th>Bond Reference</th>
                            <th>Officer Stamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td class="serial-number"><?php echo htmlspecialchars($entry['pe_serial_number']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($entry['pe_created_at'])); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $entry['pe_type'])); ?></td>
                                <td>K <?php echo number_format($entry['pe_amount'], 2); ?></td>
                                <td>K <?php echo number_format($entry['pe_balance_after'], 2); ?></td>
                                <td><?php echo $entry['pe_bond_reference'] ? htmlspecialchars($entry['pe_bond_reference']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($entry['pe_officer_stamp'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="certificate-box">
                    <p>No passbook entries found.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Official Passbook Record
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
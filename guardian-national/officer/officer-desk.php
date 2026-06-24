<?php
require_once '../config/national_db.php';
requireLogin();

// Only govt officers can access
if ($_SESSION['sv_user_type'] != 'govt_officer') {
    header("Location: ../saver/savings-book.php");
    exit();
}

$pdo = getDB();

// Get statistics
$total_savers = $pdo->query("SELECT COUNT(*) FROM savers WHERE sv_user_type = 'saver'")->fetchColumn();
$total_savings = $pdo->query("SELECT SUM(sv_savings) FROM savers WHERE sv_user_type = 'saver'")->fetchColumn();
$total_bonds = $pdo->query("SELECT SUM(sv_bond_balance) FROM savers WHERE sv_user_type = 'saver'")->fetchColumn();
$suspended = $pdo->query("SELECT COUNT(*) FROM savers WHERE sv_suspended = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Desk - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Government Officer Desk</h1>
            <h2>Official Administration Portal</h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div class="book-pages">
            <div class="left-page">
                <div class="page-title">📊 National Savings Overview</div>
                
                <div class="info-card">
                    <h3>Statistics</h3>
                    <p><strong>Total Savers:</strong> <?php echo $total_savers; ?></p>
                    <p><strong>Total Savings:</strong> K <?php echo number_format($total_savings ?? 0, 2); ?></p>
                    <p><strong>Total Bonds:</strong> K <?php echo number_format($total_bonds ?? 0, 2); ?></p>
                    <p><strong>Suspended Accounts:</strong> <?php echo $suspended; ?></p>
                </div>
            </div>
            
            <div class="right-page">
                <div class="page-title">🔧 Administrative Functions</div>
                
                <div class="nav-links" style="flex-direction: column;">
                    <a href="saver-registry.php" class="nav-link">👥 Saver Registry</a>
                    <a href="national-audit.php" class="nav-link">📋 National Audit</a>
                    <a href="../portal/logout.php" class="nav-link" style="background: var(--danger);">🚪 Logout</a>
                </div>
            </div>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Officer Desk
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
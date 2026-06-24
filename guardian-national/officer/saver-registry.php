<?php
require_once '../config/national_db.php';
requireLogin();

if ($_SESSION['sv_user_type'] != 'govt_officer') {
    header("Location: ../saver/savings-book.php");
    exit();
}

$pdo = getDB();
$message = '';

// Handle unsuspension
if (isset($_GET['unsuspend'])) {
    $stmt = $pdo->prepare("UPDATE savers SET sv_suspended = 0, sv_login_attempts = 0 WHERE sv_id = ?");
    $stmt->execute([$_GET['unsuspend']]);
    $message = 'Account unsuspended successfully.';
}

// Get all savers
$savers = $pdo->query("SELECT * FROM savers WHERE sv_user_type = 'saver' ORDER BY sv_created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saver Registry - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header">
            <div class="coat-of-arms">⚜️</div>
            <h1>Saver Registry</h1>
            <h2>All Registered Savings Accounts</h2>
            <div id="session-timer" style="color: var(--brass-light); font-size: 12px; margin-top: 5px;"></div>
        </div>
        
        <div style="padding: 30px;">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <a href="officer-desk.php" class="btn btn-brass" style="margin-bottom: 20px;">← Back to Officer Desk</a>
            
            <table class="passbook-table">
                <thead>
                    <tr>
                        <th>National ID</th>
                        <th>Full Name</th>
                        <th>Savings</th>
                        <th>Bonds</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($savers as $saver): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($saver['sv_national_id']); ?></td>
                            <td><?php echo htmlspecialchars($saver['sv_full_name']); ?></td>
                            <td>K <?php echo number_format($saver['sv_savings'], 2); ?></td>
                            <td>K <?php echo number_format($saver['sv_bond_balance'], 2); ?></td>
                            <td>
                                <span style="color: <?php echo $saver['sv_suspended'] ? 'red' : 'green'; ?>;">
                                    <?php echo $saver['sv_suspended'] ? 'Suspended' : 'Active'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($saver['sv_suspended']): ?>
                                    <a href="?unsuspend=<?php echo $saver['sv_id']; ?>" class="btn btn-primary" style="font-size: 11px; padding: 5px 10px;">Unsuspend</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="gov-footer">
            © <?php echo date('Y'); ?> Guardian National Savings Bank
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
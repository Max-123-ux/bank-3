<?php
require_once '../config/national_db.php';
requireLogin();

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM savers WHERE sv_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$bond_interest = $user['sv_bond_balance'] * (BOND_INTEREST_RATE / 100);
$total_holdings = $user['sv_savings'] + $user['sv_bond_balance'] + $bond_interest;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Certificate - Guardian National</title>
    <link rel="stylesheet" href="../assets/css/government.css">
    <style>
        @media print {
            body { background: white; }
            .book-container { box-shadow: none; border: 3px double #c5a55a; }
            .no-print { display: none; }
        }
        
        .certificate {
            background: var(--parchment);
            border: 5px double var(--brass);
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            bottom: 15px;
            border: 2px solid var(--brass-light);
            pointer-events: none;
        }
        
        .certificate h1 {
            font-size: 28px;
            color: var(--forest-green);
            letter-spacing: 3px;
        }
        
        .certificate .seal {
            width: 100px;
            height: 100px;
            border: 3px solid var(--danger);
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--danger);
            font-weight: bold;
            transform: rotate(-10deg);
        }
    </style>
</head>
<body>
    <div class="book-container">
        <div class="book-binding"></div>
        
        <div class="gov-header no-print">
            <div class="coat-of-arms">⚜️</div>
            <h1>Official Savings Certificate</h1>
            <h2>Government of Guardian National</h2>
        </div>
        
        <div style="padding: 30px;">
            <div class="no-print" style="text-align: right; margin-bottom: 20px;">
                <button onclick="window.print()" class="btn btn-brass">🖨️ Print Certificate</button>
                <a href="savings-book.php" class="btn btn-primary">← Back to Savings Book</a>
            </div>
            
            <div class="certificate">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 80px;">⚜️</div>
                </div>
                
                <h1>SAVINGS CERTIFICATE</h1>
                <p style="font-style: italic; margin: 10px 0;">Issued by the Guardian National Savings Bank</p>
                
                <hr style="border: 1px solid var(--brass); margin: 20px 0;">
                
                <p style="font-size: 16px;">This is to certify that</p>
                <h2 style="color: var(--forest-green); margin: 15px 0;"><?php echo htmlspecialchars($user['sv_full_name']); ?></h2>
                <p>National ID: <strong><?php echo htmlspecialchars($user['sv_national_id']); ?></strong></p>
                
                <hr style="border: 1px solid var(--brass); margin: 20px 0;">
                
                <table style="width: 60%; margin: 20px auto; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: left; padding: 10px; border-bottom: 1px solid var(--brass-light);">Savings Book Balance</td>
                        <td style="text-align: right; padding: 10px; border-bottom: 1px solid var(--brass-light); font-weight: bold;">K <?php echo number_format($user['sv_savings'], 2); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 10px; border-bottom: 1px solid var(--brass-light);">Government Bonds</td>
                        <td style="text-align: right; padding: 10px; border-bottom: 1px solid var(--brass-light); font-weight: bold;">K <?php echo number_format($user['sv_bond_balance'], 2); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 10px; border-bottom: 1px solid var(--brass-light);">Projected Bond Interest (<?php echo BOND_INTEREST_RATE; ?>%)</td>
                        <td style="text-align: right; padding: 10px; border-bottom: 1px solid var(--brass-light); font-weight: bold;">K <?php echo number_format($bond_interest, 2); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 10px; font-size: 18px; color: var(--forest-green);"><strong>Total Holdings</strong></td>
                        <td style="text-align: right; padding: 10px; font-size: 18px; color: var(--forest-green);"><strong>K <?php echo number_format($total_holdings, 2); ?></strong></td>
                    </tr>
                </table>
                
                <div class="seal">
                    OFFICIAL
                </div>
                
                <p style="margin-top: 20px;">Date of Issue: <?php echo date('d F Y'); ?></p>
                <p style="font-style: italic;">This certificate is a true record of holdings at the Guardian National Savings Bank</p>
                
                <div style="margin-top: 30px; display: flex; justify-content: space-between;">
                    <div style="text-align: left;">
                        <p>_________________________</p>
                        <p>Government Officer Stamp</p>
                    </div>
                    <div style="text-align: right;">
                        <p>_________________________</p>
                        <p>Account Holder Signature</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="gov-footer no-print">
            © <?php echo date('Y'); ?> Guardian National Savings Bank - Official Certificate
        </div>
    </div>
    
    <script src="../assets/js/savings-app.js"></script>
</body>
</html>
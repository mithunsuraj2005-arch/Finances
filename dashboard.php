<?php

session_start();

require_once __DIR__ . '/../database.php';

// Check user login
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'user') {
    header("Location: ../login.php?role=user");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user profile details
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_name = $user_data['name'] ?? 'User';

// 1. Total All-Time Income & Expense
$inc_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM incomes WHERE user_id = ?");
$inc_stmt->bind_param("i", $user_id);
$inc_stmt->execute();
$total_income = floatval($inc_stmt->get_result()->fetch_assoc()['total'] ?? 0);

$exp_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE user_id = ?");
$exp_stmt->bind_param("i", $user_id);
$exp_stmt->execute();
$total_expense = floatval($exp_stmt->get_result()->fetch_assoc()['total'] ?? 0);

$balance = $total_income - $total_expense;

// 2. This Month's Income & Expense
$m_inc_stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS m_total 
    FROM incomes 
    WHERE user_id = ? 
      AND MONTH(income_date) = MONTH(CURRENT_DATE()) 
      AND YEAR(income_date) = YEAR(CURRENT_DATE())
");
$m_inc_stmt->bind_param("i", $user_id);
$m_inc_stmt->execute();
$month_income = floatval($m_inc_stmt->get_result()->fetch_assoc()['m_total'] ?? 0);

$m_exp_stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS m_total 
    FROM expenses 
    WHERE user_id = ? 
      AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
      AND YEAR(expense_date) = YEAR(CURRENT_DATE())
");
$m_exp_stmt->bind_param("i", $user_id);
$m_exp_stmt->execute();
$month_expense = floatval($m_exp_stmt->get_result()->fetch_assoc()['m_total'] ?? 0);

$month_savings = $month_income - $month_expense;

// 3. Total Transaction Count
$cnt_inc = intval($conn->query("SELECT COUNT(*) AS c FROM incomes WHERE user_id = $user_id")->fetch_assoc()['c'] ?? 0);
$cnt_exp = intval($conn->query("SELECT COUNT(*) AS c FROM expenses WHERE user_id = $user_id")->fetch_assoc()['c'] ?? 0);
$total_txns = $cnt_inc + $cnt_exp;

// 4. Combined Recent Transactions (Latest 6)
$recent_sql = "
    SELECT id, 'income' AS type, category, amount, income_date AS txn_date, description 
    FROM incomes WHERE user_id = ?
    UNION ALL
    SELECT id, 'expense' AS type, category, amount, expense_date AS txn_date, description 
    FROM expenses WHERE user_id = ?
    ORDER BY txn_date DESC, id DESC 
    LIMIT 6
";
$rec_stmt = $conn->prepare($recent_sql);
$rec_stmt->bind_param("ii", $user_id, $user_id);
$rec_stmt->execute();
$recent_res = $rec_stmt->get_result();

// 5. Top Spending Categories
$top_cats_stmt = $conn->prepare("
    SELECT category, SUM(amount) AS cat_total, COUNT(*) AS cat_count 
    FROM expenses 
    WHERE user_id = ? 
    GROUP BY category 
    ORDER BY cat_total DESC 
    LIMIT 4
");
$top_cats_stmt->bind_param("i", $user_id);
$top_cats_stmt->execute();
$top_cats = $top_cats_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Dashboard - Expense Finance</title>

    <!-- Common Sidebar CSS -->
    <link rel="stylesheet" href="../css/sidebar.css">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 28px 32px;
            border-radius: 14px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
        }

        .welcome-banner h1 {
            font-size: 26px;
            margin-bottom: 6px;
        }

        .welcome-banner p {
            color: #bfdbfe;
            font-size: 14px;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .quick-btn {
            background: rgba(255, 255, 255, 0.16);
            color: white;
            padding: 9px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .quick-btn:hover {
            background: white;
            color: #1e3a8a;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .stat-info {
            min-width: 0;
        }

        .stat-info .label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-info .value {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .amt-income { color: #16a34a; font-weight: 700; }
        .amt-expense { color: #dc2626; font-weight: 700; }

        .two-column {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 25px;
        }

        .box {
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .box-header h2 {
            font-size: 17px;
            color: #172554;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .box-header a {
            font-size: 13px;
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 11px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        tbody td {
            padding: 13px 16px;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .badge-income {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-expense {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .cat-item {
            margin-bottom: 18px;
        }

        .cat-item:last-child {
            margin-bottom: 0;
        }

        .cat-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .progress-bar-bg {
            background: #f1f5f9;
            height: 8px;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar-fill {
            background: #dc2626;
            height: 100%;
            border-radius: 5px;
        }

        @media (max-width: 1024px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

<!-- COMMON SIDEBAR -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="main-content">

    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
        <div>
            <h1>Welcome, <?= htmlspecialchars($user_name) ?> 👋</h1>
            <p>
                <i class="fa-regular fa-calendar" style="margin-right:5px;"></i>
                <?= date('l, d F Y') ?> &bull; Here is your financial overview
            </p>
        </div>

        <!-- QUICK SHORTCUTS -->
        <div class="quick-actions">
            <a href="income/add.php" class="quick-btn">
                <i class="fa-solid fa-arrow-trend-up"></i> Add Income
            </a>
            <a href="expense/add.php" class="quick-btn">
                <i class="fa-solid fa-arrow-trend-down"></i> Add Expense
            </a>
            <a href="categories/add.php" class="quick-btn">
                <i class="fa-solid fa-tag"></i> Add Category
            </a>
            <a href="reports/index.php" class="quick-btn">
                <i class="fa-solid fa-chart-column"></i> Reports
            </a>
        </div>
    </div>

    <!-- STATS GRID -->
    <div class="stats-grid">

        <!-- TOTAL INCOME -->
        <div class="stat-card" style="border-left:4px solid #16a34a;">
            <div class="stat-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="stat-info">
                <div class="label">Total Income</div>
                <div class="value amt-income">₹<?= number_format($total_income, 2); ?></div>
            </div>
        </div>

        <!-- TOTAL EXPENSE -->
        <div class="stat-card" style="border-left:4px solid #dc2626;">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626;">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div class="stat-info">
                <div class="label">Total Expense</div>
                <div class="value amt-expense">₹<?= number_format($total_expense, 2); ?></div>
            </div>
        </div>

        <!-- NET BALANCE -->
        <div class="stat-card" style="border-left:4px solid #2563eb;">
            <div class="stat-icon" style="background:#dbeafe;color:#2563eb;">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-info">
                <div class="label">Net Balance</div>
                <div class="value" style="color:<?= $balance >= 0 ? '#16a34a' : '#dc2626' ?>;">
                    <?= ($balance >= 0 ? '+' : '-') . '₹' . number_format(abs($balance), 2); ?>
                </div>
            </div>
        </div>

        <!-- THIS MONTH'S SPENDING -->
        <div class="stat-card" style="border-left:4px solid #f59e0b;">
            <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <div class="label">This Month (<?= date('M') ?>)</div>
                <div class="value" style="font-size:20px;color:#0f172a;">
                    <span class="amt-expense">₹<?= number_format($month_expense, 2); ?></span>
                </div>
            </div>
        </div>

    </div>

    <!-- TWO COLUMN SECTION -->
    <div class="two-column">

        <!-- 1. RECENT TRANSACTIONS -->
        <div class="box">

            <div class="box-header">
                <h2>
                    <i class="fa-solid fa-receipt" style="color:#2563eb;"></i>
                    Recent Transactions
                </h2>
                <a href="reports/index.php">View Reports &rarr;</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recent_res && $recent_res->num_rows > 0): ?>
                    <?php while ($txn = $recent_res->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M', strtotime($txn['txn_date'])); ?></td>
                            <td><strong><?= htmlspecialchars($txn['category'] ?? 'General'); ?></strong></td>
                            <td>
                                <?php if ($txn['type'] === 'income'): ?>
                                    <span class="badge-income"><i class="fa-solid fa-arrow-up"></i> In</span>
                                <?php else: ?>
                                    <span class="badge-expense"><i class="fa-solid fa-arrow-down"></i> Out</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= $txn['type'] === 'income' ? 'amt-income' : 'amt-expense' ?>">
                                    <?= $txn['type'] === 'income' ? '+' : '-' ?>₹<?= number_format($txn['amount'], 2); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($txn['description'] ?: '—'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:35px;color:#94a3b8;">
                            <i class="fa-solid fa-receipt" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                            No transactions recorded yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

        </div>

        <!-- 2. TOP SPENDING CATEGORIES -->
        <div class="box">

            <div class="box-header">
                <h2>
                    <i class="fa-solid fa-chart-pie" style="color:#2563eb;"></i>
                    Top Expenses by Category
                </h2>
                <a href="expense/index.php">Expenses &rarr;</a>
            </div>

            <?php if ($top_cats && $top_cats->num_rows > 0): ?>
                <?php while ($cat = $top_cats->fetch_assoc()): 
                    $c_amt = floatval($cat['cat_total']);
                    $pct   = $total_expense > 0 ? round(($c_amt / $total_expense) * 100) : 0;
                ?>
                    <div class="cat-item">
                        <div class="cat-info">
                            <span><strong><?= htmlspecialchars(ucfirst($cat['category'])) ?></strong> (<?= $cat['cat_count'] ?> txns)</span>
                            <span class="amt-expense">₹<?= number_format($c_amt, 2) ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?= min($pct, 100) ?>%;"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fa-solid fa-tags" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                    No expense data available.
                </div>
            <?php endif; ?>

            <!-- MONTHLY SUMMARY MINI BOX -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:15px;margin-top:25px;">
                <div style="font-size:13px;color:#64748b;margin-bottom:6px;font-weight:600;">
                    THIS MONTH'S BALANCE (<?= date('F') ?>):
                </div>
                <div style="font-size:18px;font-weight:700;color:<?= $month_savings >= 0 ? '#16a34a' : '#dc2626' ?>;">
                    <?= ($month_savings >= 0 ? '+' : '-') ?>₹<?= number_format(abs($month_savings), 2) ?>
                </div>
            </div>

        </div>

    </div>

</main>

</body>

</html>
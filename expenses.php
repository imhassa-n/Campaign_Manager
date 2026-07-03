<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';
require_permission('expenses');

if(isset($_POST['save']))
{
    $title = $_POST['title'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $notes = $_POST['notes'];

    mysqli_query($conn,"
    INSERT INTO expenses(title,category,amount,expense_date,notes)
    VALUES('$title','$category','$amount','$expense_date','$notes')
    ");

    header("Location: expenses.php");
    exit;
}

// Summary Stats
$total_expenses = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(amount),0) as total FROM expenses"))['total'];
$this_month = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(amount),0) as total FROM expenses WHERE MONTH(expense_date)=MONTH(CURDATE()) AND YEAR(expense_date)=YEAR(CURDATE())"))['total'];
$last_month = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(amount),0) as total FROM expenses WHERE MONTH(expense_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(expense_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"))['total'];
$total_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as cnt FROM expenses"))['cnt'];

// Category-wise summary
$category_summary = mysqli_query($conn,"SELECT category, SUM(amount) as total, COUNT(*) as cnt FROM expenses GROUP BY category ORDER BY total DESC");

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Expenses</h1>
            <p>Track and manage your agency expenses</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search expenses..." id="expenseSearch" onkeyup="filterTable()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Summary Cards -->
    <div class="grid-4 mb-4">
        <div class="metric-card">
            <div class="metric-icon danger">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="metric-label">Total Expenses</div>
            <div class="metric-value">Rs <?php echo number_format($total_expenses); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon warning">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="metric-label">This Month</div>
            <div class="metric-value">Rs <?php echo number_format($this_month); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon teal">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="metric-label">Last Month</div>
            <div class="metric-value">Rs <?php echo number_format($last_month); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon navy">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="metric-label">Total Entries</div>
            <div class="metric-value"><?php echo $total_count; ?></div>
        </div>
    </div>

    <div class="row">
        <!-- Left: Add Form + Table -->
        <div class="col-md-8">
            <!-- Add Expense Form -->
            <div class="page-card mb-4">
                <div class="page-card-header">
                    <h2><i class="bi bi-plus-circle-fill"></i> Add New Expense</h2>
                </div>
                <div class="page-card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-section">
                                    <label class="form-label">Expense Title</label>
                                    <input type="text"
                                           name="title"
                                           class="form-control"
                                           placeholder="e.g., Facebook Ads, Hosting, Software"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-section">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-control form-select" required>
                                        <option value="Ads Spend">Ads Spend</option>
                                        <option value="Software">Software / Tools</option>
                                        <option value="Hosting">Hosting / Domain</option>
                                        <option value="Salary">Salary</option>
                                        <option value="Office">Office</option>
                                        <option value="Travel">Travel</option>
                                        <option value="General" selected>General</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-section">
                                    <label class="form-label">Amount (Rs)</label>
                                    <input type="number"
                                           name="amount"
                                           class="form-control"
                                           placeholder="0"
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-section">
                                    <label class="form-label">Date</label>
                                    <input type="date"
                                           name="expense_date"
                                           class="form-control"
                                           value="<?php echo date('Y-m-d'); ?>"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-section">
                                    <label class="form-label">Notes (Optional)</label>
                                    <input type="text"
                                           name="notes"
                                           class="form-control"
                                           placeholder="Any extra details...">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="save" class="btn-brand">
                            <i class="bi bi-plus-circle-fill"></i>
                            Save Expense
                        </button>
                    </form>
                </div>
            </div>

            <!-- Expenses List -->
            <div class="page-card">
                <div class="page-card-header">
                    <h2><i class="bi bi-receipt-cutoff"></i> All Expenses</h2>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <select id="categoryFilter" onchange="filterTable()" class="form-control form-select" style="width: auto; height: 34px; font-size: 12px; padding: 4px 30px 4px 10px;">
                            <option value="">All Categories</option>
                            <option value="Ads Spend">Ads Spend</option>
                            <option value="Software">Software / Tools</option>
                            <option value="Hosting">Hosting / Domain</option>
                            <option value="Salary">Salary</option>
                            <option value="Office">Office</option>
                            <option value="Travel">Travel</option>
                            <option value="General">General</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="page-card-body" style="padding: 0;">
                    <div class="table-wrapper">
                        <table class="modern-table" id="expensesTable">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $expenses = mysqli_query($conn,"SELECT * FROM expenses ORDER BY expense_date DESC, id DESC");

                            while($expense = mysqli_fetch_assoc($expenses))
                            {
                                $cat = $expense['category'] ?? 'General';
                                // Category icon + color
                                $cat_colors = [
                                    'Ads Spend' => ['#3b82f6', 'bi-megaphone-fill'],
                                    'Software' => ['#8b5cf6', 'bi-code-slash'],
                                    'Hosting' => ['#06b6d4', 'bi-cloud-fill'],
                                    'Salary' => ['#10b981', 'bi-people-fill'],
                                    'Office' => ['#f59e0b', 'bi-building'],
                                    'Travel' => ['#ef4444', 'bi-airplane-fill'],
                                    'General' => ['#6b7280', 'bi-tag-fill'],
                                    'Other' => ['#78716c', 'bi-three-dots'],
                                ];
                                $color = $cat_colors[$cat][0] ?? '#6b7280';
                                $icon = $cat_colors[$cat][1] ?? 'bi-tag-fill';
                            ?>
                            <tr data-category="<?php echo $cat; ?>">
                                <td>
                                    <div style="font-weight: 600; color: var(--navy-800);">
                                        <?php echo htmlspecialchars($expense['title']); ?>
                                    </div>
                                    <?php if(!empty($expense['notes'])): ?>
                                    <div style="font-size: 11px; color: var(--gray-500); margin-top: 2px;">
                                        <?php echo htmlspecialchars($expense['notes']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: <?php echo $color; ?>15; color: <?php echo $color; ?>;">
                                        <i class="bi <?php echo $icon; ?>" style="font-size: 10px;"></i>
                                        <?php echo $cat; ?>
                                    </span>
                                </td>
                                <td style="font-weight: 500; color: var(--navy-600); font-size: 13px;">
                                    <?php echo $expense['expense_date'] ? date('d M, Y', strtotime($expense['expense_date'])) : '-'; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--danger);">
                                        Rs <?php echo number_format($expense['amount']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a class="action-btn edit"
                                           href="edit_expense.php?id=<?php echo $expense['id']; ?>"
                                           title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a class="action-btn delete"
                                           href="delete_expense.php?id=<?php echo $expense['id']; ?>"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this expense?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Category Summary -->
        <div class="col-md-4">
            <div class="page-card" style="position: sticky; top: 20px;">
                <div class="page-card-header">
                    <h2><i class="bi bi-pie-chart-fill"></i> Category Breakdown</h2>
                </div>
                <div class="page-card-body">
                    <?php
                    if(mysqli_num_rows($category_summary) == 0) {
                        echo '<div style="text-align: center; padding: 30px; color: var(--gray-500);">No expenses recorded yet.</div>';
                    } else {
                        while($cs = mysqli_fetch_assoc($category_summary)) {
                            $cat = $cs['category'] ?? 'General';
                            $cat_colors = [
                                'Ads Spend' => '#3b82f6', 'Software' => '#8b5cf6', 'Hosting' => '#06b6d4',
                                'Salary' => '#10b981', 'Office' => '#f59e0b', 'Travel' => '#ef4444',
                                'General' => '#6b7280', 'Other' => '#78716c'
                            ];
                            $color = $cat_colors[$cat] ?? '#6b7280';
                            $pct = $total_expenses > 0 ? round(($cs['total'] / $total_expenses) * 100) : 0;
                    ?>
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-weight: 600; font-size: 13px; color: var(--navy-800);"><?php echo $cat; ?></span>
                            <span style="font-weight: 700; font-size: 13px; color: <?php echo $color; ?>;">Rs <?php echo number_format($cs['total']); ?> <span style="font-size: 10px; color: var(--gray-400);">(<?php echo $pct; ?>%)</span></span>
                        </div>
                        <div style="width: 100%; height: 8px; background: var(--gray-100); border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $color; ?>; border-radius: 10px; transition: width 0.6s ease;"></div>
                        </div>
                        <div style="font-size: 11px; color: var(--gray-500); margin-top: 3px;"><?php echo $cs['cnt']; ?> entries</div>
                    </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
function filterTable() {
    const query = document.getElementById('expenseSearch').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const rows = document.querySelectorAll('#expensesTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const rowCat = row.getAttribute('data-category');
        const matchText = text.includes(query);
        const matchCat = !category || rowCat === category;
        row.style.display = (matchText && matchCat) ? '' : 'none';
    });
}
</script>

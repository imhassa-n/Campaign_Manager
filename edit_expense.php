<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';

$id = $_GET['id'];

$result = mysqli_query($conn,"
SELECT * FROM expenses
WHERE id='$id'
");

$expense = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    $title = $_POST['title'];
    $amount = $_POST['amount'];

    mysqli_query($conn,"
    UPDATE expenses
    SET
    title='$title',
    amount='$amount'
    WHERE id='$id'
    ");

    header("Location: expenses.php");
    exit;
}

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Edit Expense</h1>
            <p>Update expense record</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="expenses.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Expenses
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-pencil-square"></i> Edit Expense</h2>
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
                                   value="<?php echo $expense['title']; ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Amount (Rs)</label>
                            <input type="number"
                                   name="amount"
                                   class="form-control"
                                   value="<?php echo $expense['amount']; ?>"
                                   required>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="update" class="btn-brand">
                        <i class="bi bi-check-circle-fill"></i>
                        Update Expense
                    </button>
                    <a href="expenses.php" class="btn-back">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
<?php
include 'db.php';

// Add new columns to expenses table
$queries = [
    "ALTER TABLE expenses ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER title",
    "ALTER TABLE expenses ADD COLUMN expense_date DATE DEFAULT NULL AFTER amount",
    "ALTER TABLE expenses ADD COLUMN notes TEXT DEFAULT NULL AFTER expense_date",
    "ALTER TABLE payments ADD COLUMN payment_method VARCHAR(50) DEFAULT 'Cash' AFTER payment_date",
    "ALTER TABLE payments ADD COLUMN notes TEXT DEFAULT NULL AFTER payment_method",
];

foreach($queries as $q) {
    if(!mysqli_query($conn, $q)) {
        echo "Note: " . mysqli_error($conn) . "<br>";
    } else {
        echo "OK: " . substr($q, 0, 60) . "...<br>";
    }
}

// Update existing expenses with today's date where null
mysqli_query($conn, "UPDATE expenses SET expense_date = CURDATE() WHERE expense_date IS NULL");

echo "<br><strong>Finance tables updated successfully!</strong>";
echo "<br><a href='expenses.php'>Go to Expenses</a> | <a href='payments.php'>Go to Payments</a>";
?>

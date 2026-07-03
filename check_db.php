<?php
include 'db.php';
$res = mysqli_query($conn, "SHOW TABLES");
while($row = mysqli_fetch_array($res)) {
    echo "Table: " . $row[0] . "\n";
    $cols = mysqli_query($conn, "DESCRIBE " . $row[0]);
    while($c = mysqli_fetch_assoc($cols)) {
        echo "  - " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }
    echo "\n";
}
?>

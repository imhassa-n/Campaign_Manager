<?php
$conn = mysqli_connect("localhost", "root", "", "campaign_manager");
$q = mysqli_query($conn, "SELECT * FROM users WHERE id=1");
print_r(mysqli_fetch_assoc($q));
?>

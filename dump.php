<?php
$c = mysqli_connect('localhost','root','','campaign_manager');
$res = mysqli_query($c, "DESCRIBE campaigns");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>

<?php
$conn=mysqli_connect('localhost','root','','campaign_manager'); 
$res=mysqli_query($conn,'DESCRIBE payments'); 
while($r=mysqli_fetch_assoc($res)) print_r($r);
?>

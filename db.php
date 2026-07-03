<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "campaign_manager"
);

if(!$conn){
    die("Database Connection Failed");
}

?>
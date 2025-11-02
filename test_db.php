<?php
require 'db_connect.php';
if($conn){
    echo "DB Connected!";
} else {
    echo "DB connection failed!";
}
?>

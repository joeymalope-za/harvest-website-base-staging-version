<?php

$test = mysqli_connect('mariadb', 'harvestj', 'harvestj');
if (!$test) {
    die('MySQL Error: ' . mysqli_error());
}
echo 'Database connection is working properly!';
mysqli_close($testConnection);
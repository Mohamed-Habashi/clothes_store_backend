<?php
$servername = "mysql:host=localhost;dbname=e-commerce";
$username = "root";
$password = "";
$options=array(
    PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES UTF8"
);
try {
    $conn = new PDO($servername, $username, $password,$options);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    include "functions.php";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
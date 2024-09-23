<?php
$mysqli = new mysqli("localhost", "root", "", "paystack_voucher");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>
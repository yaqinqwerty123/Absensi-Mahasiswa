<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$server   = "localhost";
$username = "umum";
$password = "RSmndb2020";
$database = "rs_pendidikan";

// $server   = "localhost";
// $username = "root";
// $password = "";
// $database = "rs_pendidikan";



$con = mysql_connect($server, $username , $password) or die("Koneksi gagal : " . mysql_error());
mysql_select_db($database, $con) or die("Database tidak bisa dibuka");
?>
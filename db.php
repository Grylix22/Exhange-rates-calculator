<?php

function conn_open()
 {
 $dbhost = "localhost";
 $dbuser = "root";
//  $dbuser = "id20945551_administrator";
 $dbpass = "";
//  $dbpass = "zAzadanie0!";
 $db = "kursy_walut";
// $db = "id20945551_01kursy_walut";


 $conn = new mysqli($dbhost, $dbuser, $dbpass, $db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 }
 
function conn_close($conn)
 {
 $conn -> close();
 }
   
?>
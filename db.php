<?php

function conn_open()
 {
 $dbhost = "localhost";
 $dbuser = "root";
 $dbpass = "";
 $db = "kursy_walut";


 $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
 
 return $conn;
 }
 
function conn_close($conn)
 {
 $conn -> close();
 }
   
?>
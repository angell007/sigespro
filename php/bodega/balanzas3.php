<?php
$peso5 = ( isset( $_REQUEST['peso5'] ) ? $_REQUEST['peso5'] : '' );
$peso6 = ( isset( $_REQUEST['peso6'] ) ? $_REQUEST['peso6'] : '' );


$fp1 = fopen("balanza5.txt", "w+");
fputs($fp1, $peso1);
fclose($fp1);

$fp2 = fopen("balanza6.txt", "w+");
fputs($fp2, $peso2);
fclose($fp2);

?>
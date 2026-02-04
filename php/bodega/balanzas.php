<?php
$peso1 = ( isset( $_REQUEST['peso1'] ) ? $_REQUEST['peso1'] : '' );
$peso2 = ( isset( $_REQUEST['peso2'] ) ? $_REQUEST['peso2'] : '' );
/*
$peso3 = ( isset( $_REQUEST['peso1'] ) ? $_REQUEST['peso3'] : '' );
$peso4 = ( isset( $_REQUEST['peso2'] ) ? $_REQUEST['peso4'] : '' );
*/


$fp1 = fopen("balanza1.txt", "w+");
fputs($fp1, $peso1);
fclose($fp1);

$fp2 = fopen("balanza2.txt", "w+");
fputs($fp2, $peso2);
fclose($fp2);
/*
$fp3 = fopen("balanza3.txt", "w+");
fputs($fp3, $peso3);
fclose($fp3);

$fp4 = fopen("balanza4.txt", "w+");
fputs($fp4, $peso4);
fclose($fp4); */
?>
<?php
$peso = ( isset( $_REQUEST['peso'] ) ? $_REQUEST['peso'] : '' );
$fp = fopen("balanza1.txt", "w+");
fputs($fp, $peso);
fclose($fp);
?>
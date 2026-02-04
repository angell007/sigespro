<?php
$peso = ( isset( $_REQUEST['peso'] ) ? $_REQUEST['peso'] : '' );
$fp = fopen("balanza2.txt", "w+");
fputs($fp, $peso);
fclose($fp);
?>
<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once('../../class/class.consulta_paginada.php');
require_once('../../helper/response.php');

$name = ( isset( $_REQUEST['name'] ) ? $_REQUEST['name'] : '' );

$query="SELECT  SQL_CALC_FOUND_ROWS  Nombre As label, Id_Punto_Dispensacion As value FROM Punto_Dispensacion WHERE  No_Pos = 'Si'";

$oCon=new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$data=$oCon->getData();
unset($oCon);

show($data);

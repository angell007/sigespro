<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once('../../class/class.consulta_paginada.php');
require_once('../../helper/response.php');

$currentPage = (isset($_REQUEST['current_page']) && $_REQUEST['current_page'] != '' )? $_REQUEST['current_page'] : false;
$params = (isset($_REQUEST['filtros']) && $_REQUEST['filtros'] != '' )? $_REQUEST['filtros'] : false;
$params = json_decode($params,true);

$cond = ' WHERE Tipo = ' . '"Despacho"' ;
$query="SELECT  SQL_CALC_FOUND_ROWS   Nombre As label, Id_Bodega_Nuevo As value FROM Bodega_Nuevo";

$oCon=new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$data=$oCon->getData();
unset($oCon);

show($data);


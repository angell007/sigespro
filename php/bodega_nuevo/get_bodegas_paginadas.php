<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once('../../class/class.consulta_paginada.php');

$currentPage = (isset($_REQUEST['current_page']) && $_REQUEST['current_page'] != '' )? $_REQUEST['current_page'] : false;
$params = (isset($_REQUEST['filtros']) && $_REQUEST['filtros'] != '' )? $_REQUEST['filtros'] : false;
$params = json_decode($params,true);
$tamPage=10;
$limit;

if ($params) {

    $cond = '';

    if ($params['Nombre']) {
        $cond.= $cond == '' ? ' WHERE' : ' AND ';
        $cond.=  ' Nombre LIKE "%'.$params['Nombre'].'%" ';
    }
    if ($params['Direccion']) {
        $cond.= $cond == '' ? ' WHERE' : ' AND ';
        $cond.=  ' Direccion LIKE "%'.$params['Direccion'].'%" ';
    }
    if ($params['Telefono']) {
        $cond.= $cond == '' ? ' WHERE' : ' AND ';
        $cond.=  ' Telefono = "'.$params['Telefono'].'" ';
    }
    if ($params['Compra_Internacional']) {
        $cond.= $cond == '' ? ' WHERE' : ' AND ';
        $cond.=  ' Compra_Internacional = "'.$params['Compra_Internacional'].'" ';
    }
    if ($params['Tipo']) {
        $cond.= $cond == '' ? ' WHERE' : ' AND ';
        $cond.=  ' Tipo = "'.$params['Tipo'].'" ';
    }
}


if( !$currentPage){
    $limit = 0;
}else{
    $limit = ($currentPage-1)*$tamPage;
}

$query=" Select  SQL_CALC_FOUND_ROWS  * From Bodega_Nuevo
        $cond
        LIMIT  $limit , $tamPage" ;

$oCon=new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$data=$oCon->getData();
unset($oCon);

echo json_encode($data);


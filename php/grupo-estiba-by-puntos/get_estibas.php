<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once '../../class/class.consulta_paginada.php';
require_once('../../helper/response.php');

$punto=isset( $_REQUEST['Id_Punto'] ) ? $_REQUEST['Id_Punto'] : false ;
$grupo=isset( $_REQUEST['Id_Grupo_Estiba'] ) ? $_REQUEST['Id_Grupo_Estiba'] : false ;
$filtros=isset( $_REQUEST['Filtros'] ) ? $_REQUEST['Filtros'] : false ;
$currentPage=isset( $_REQUEST['currentPage'] ) ? $_REQUEST['currentPage'] : false ;
$limitPage=isset( $_REQUEST['limit'] ) ? $_REQUEST['limit'] : false ;
$result=[];

$filtros = json_decode($filtros,true);

$select =  isset( $_REQUEST['Select'] ) ? true : false ;

$condicion='';

if ($punto) {
    $condicion .= ' WHERE Id_punto_Dispensacion = ' .$punto; 
}

if ($grupo) {
    $condicion = ' WHERE Id_Grupo_Estiba = ' .$grupo;     
}

if ($filtros['Nombre'] ) {
    $condicion .= $condicion != '' ? ' AND ' : ' WHERE';
    $condicion.= ' Nombre LIKE "%'.$filtros['Nombre'].'%"';
}
if ($filtros['Codigo_Barras'] ) {
    $condicion .= $condicion != '' ? ' AND ' : ' WHERE';
    $condicion.= ' Codigo_Barras LIKE "%'.$filtros['Codigo_Barras'].'%"';
}
if ($filtros['Estado'] ) {
    $condicion .= $condicion != '' ? ' AND ' : ' WHERE';
    $condicion.= ' Estado = "'.$filtros['Estado'].'"';
}

$limit = '';

if ( $currentPage && $limitPage) {
    (int)$currentPage-=1;
    
    $currentPage = $currentPage * (int)$limitPage;

    $limit.= ' LIMIT '.$currentPage .' , '.$limitPage.' ';
}



if ($select) {
    $query='SELECT  SQL_CALC_FOUND_ROWS * , Id_Estiba AS value , Nombre AS label FROM Estiba ' . $condicion;
}else{
    $query='SELECT SQL_CALC_FOUND_ROWS * , "false" AS Selected FROM Estiba ' . $condicion.$limit;
    
}

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$estibas = $oCon->getData();

$result['data']= $estibas['data'] ? $estibas['data'] : [];
$result['numReg']=$estibas['total'];
$result['tipo']='success';

show($result);


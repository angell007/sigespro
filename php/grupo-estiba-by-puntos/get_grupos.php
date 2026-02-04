<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
require_once('../../helper/response.php');
include_once '../../class/class.consulta_paginada.php';

$idPunto = isset($_REQUEST['idPunto']) ? $_REQUEST['idPunto'] : '';

$label = isset($_REQUEST['label']) ? $_REQUEST['label'] : '';
$filtros = isset($_REQUEST['filtros']) ? $_REQUEST['filtros'] : '';
$filtros = json_decode($filtros,true);

$currentPage = isset($_REQUEST['currentPage']) ? $_REQUEST['currentPage'] : '';
$limitPunto = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : '';

if ($idPunto) {
    
    $limit='';

    if($currentPage){
        (int)$currentPage -=1;
        $currentPage *= $limitPunto;
        $limit= ' LIMIT '.$currentPage.' , '. $limitPunto;
    }

    $condicion = '';

    if ($filtros['Nombre'] ) {
        $condicion.= ' AND Nombre LIKE "%'.$filtros['Nombre'].'%"';
    }

    if ($filtros['Fecha_Vencimiento'] ) {
        $condicion.= ' AND Fecha_Vencimiento = "'.$filtros['Fecha_Vencimiento'].'"';
    }

    if ($filtros['Presentacion'] ) {
        $condicion.= ' AND Presentacion = "'.$filtros['Presentacion'].'"';
    }

    if ($label) {
        $query = 'SELECT SQL_CALC_FOUND_ROWS Nombre AS label, Id_Grupo_Estiba as value';
    } else {
        $query = 'SELECT SQL_CALC_FOUND_ROWS  Nombre, Id_Grupo_Estiba ';
    }

    $query .= ', Fecha_Vencimiento, Presentacion, false AS Selected
     FROM Grupo_Estiba WHERE Id_Punto_Dispensacion = ' . $idPunto .$condicion. $limit;
  
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $grupos = $oCon->getData();

      unset($oCon);
      
        $res['Tipo']='success';
        $res['Grupos']=$grupos['data'] ? $grupos['data'] : [];
        $res['numReg']=$grupos['total'];
        show($res);
   
} else {
    show('faltan datos necesarios'); 
}


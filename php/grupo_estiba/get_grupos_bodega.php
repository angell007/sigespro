<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once '../../class/class.consulta_paginada.php';

$idBodega = isset($_REQUEST['id_bodega_nuevo']) ? $_REQUEST['id_bodega_nuevo'] : '';

$label = isset($_REQUEST['label']) ? $_REQUEST['label'] : '';
$filtros = isset($_REQUEST['filtros']) ? $_REQUEST['filtros'] : '';
$filtros = json_decode($filtros,true);

$currentPage = isset($_REQUEST['currentPage']) ? $_REQUEST['currentPage'] : '';
$limitBodega = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : '';

if ($idBodega) {
    # code...
    $limit='';
    if($currentPage){
        (int)$currentPage -=1;
        $currentPage *= $limitBodega;
        $limit= ' LIMIT '.$currentPage.' , '. $limitBodega;
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
        $todos=array('label'=>'TODOS', 'value'=>'-1', 'Fecha_Vencimiento'=>'Si', 'Selected'=>'0');
        $query = 'SELECT SQL_CALC_FOUND_ROWS Nombre AS label, Id_Grupo_Estiba as value';
    } else {
        $query = 'SELECT SQL_CALC_FOUND_ROWS  Nombre, Id_Grupo_Estiba ';
    }
    $query .= ', Fecha_Vencimiento, Presentacion, false AS Selected
     FROM Grupo_Estiba WHERE Id_Bodega_Nuevo = ' . $idBodega .$condicion. $limit;
  

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');

    $grupos = $oCon->getData();
    unset($oCon);
    
    
    $query_count="SELECT COUNT(*)as Cant FROM  Grupo_Estiba WHERE Id_Bodega_Nuevo = $idBodega $condicion";
    $oCon = new consulta();
    $oCon->setQuery($query_count);
    $cant= $oCon->getData()['Cant'];
    unset($oCon);

   
    $data=[];
    if($grupos['data'])   {
        $todos?array_push($grupos['data'],$todos):'';
        $data=$grupos['data'];
    }

        $res['Tipo']='success';
        $res['Grupos']= $data;
        $res['numReg']=$cant;
        echo json_encode($res);
   
} else {
    echo 'faltan datos necesarios';
}


<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

include_once('../../class/class.consulta.php');
$id_bodega_nuevo = (isset($_REQUEST['id_bodega_nuevo']) ? $_REQUEST['id_bodega_nuevo'] : '');
$label =  (isset($_REQUEST['label']) ? $_REQUEST['label'] : false);



if ($id_bodega_nuevo) {
    if ($label) {
        $query = 'SELECT C.Nombre AS label, CONCAT("C-",C.Id_Categoria_Nueva) as value';
       
    } else {
        $query = 'SELECT C.Nombre, C.Id_Categoria_Nueva ';
        
    }
    $query.=' FROM Bodega_Nuevo_Categoria_Nueva BC
    INNER JOIN Categoria_Nueva C
    ON C.Id_Categoria_Nueva= BC.Id_Categoria_Nueva
    WHERE BC.Id_Bodega_Nuevo=' . $id_bodega_nuevo;

    $oCon = new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $categorias = $oCon->getData();
    unset($oCon);

    $resultado["Tipo"] = 'success';
    $resultado["Mensaje"] = 'Categorias Encontradas';
    $resultado["Categorias"] = $categorias;
} else {

    $resultado["Tipo"] = 'error';
    $resultado["Mensaje"] = 'Debe ingresar una bodega para realizar la busqueda de las categorias';
}

echo json_encode($resultado);

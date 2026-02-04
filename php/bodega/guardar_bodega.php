<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$categorias = isset($_REQUEST['categorias']) ? $_REQUEST['categorias'] : false;

if ($datos && $categorias) {
    $datos = json_decode($datos, true);
    $categorias = json_decode($categorias, true);

    $oItem = null;
    if (isset($datos['Id_Bodega']) && $datos['Id_Bodega'] != '') {
        $oItem = new complex('Bodega','Id_Bodega',$datos['Id_Bodega']);
    } else {
        $oItem = new complex('Bodega','Id_Bodega');
    }

    foreach ($datos as $index => $value) {
        if ($index != 'Id_Bodega')
            $oItem->$index = $value;
    }
    $oItem->save();
    $id = false;
    if (isset($datos['Id_Bodega']) && $datos['Id_Bodega'] != '') {
        $id = $datos['Id_Bodega'];

        resetAsociacionCategorias($id);
        asociarCategorias($id);
    } else {
        $id = $oItem->getId();

        asociarCategorias($id);
    }
    unset($oItem);

}

function asociarCategorias($id_bodega) {
    global $categorias;

    foreach ($categorias as $i => $categoria) {
        $oItem = new complex('Bodega_Categoria','Id_Bodega_Categoria');
        $oItem->Id_Bodega = $id_bodega;
        $oItem->Id_Categoria = $categoria;
        $oItem->save();
        unset($oItem);
    }

    return;
}

function resetAsociacionCategorias($id_bodega) {
    $query = "DELETE FROM Bodega_Categoria WHERE Id_Bodega = $id_bodega";
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}

?>
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$subcategorias = isset($_REQUEST['subcategorias']) ? $_REQUEST['subcategorias'] : false;

if ($datos && $subcategorias) {
    $datos = json_decode($datos, true);
    $subcategorias = json_decode($subcategorias, true);

    $oItem = null;
    if (isset($datos['Id_Categoria_Nueva']) && $datos['Id_Categoria_Nueva'] != '') {
        $oItem = new complex('Categoria_Nueva','Id_Categoria_Nueva',$datos['Id_Categoria_Nueva']);
    } else {
        $oItem = new complex('Categoria_Nueva','Id_Categoria_Nueva');
    }

    foreach ($datos as $index => $value) {
        if ($index != 'Id_Categoria_Nueva')
            $oItem->$index = $value;
    }
    $oItem->save();
    $id = false;
    if (isset($datos['Id_Categoria_Nueva']) && $datos['Id_Categoria_Nueva'] != '') {
        $id = $datos['Id_Categoria_Nueva'];

        resetAsociacionSubcategorias($id);
        asociarSubcategorias($id);
    } else {
        $id = $oItem->getId();

        asociarSubcategorias($id);
    }
    unset($oItem);

}

function asociarSubcategorias($id_categoria) {
    global $subcategorias;

    foreach ($subcategorias as $i => $subcategoria) {
        $oItem = new complex('Categoria_Nueva_Subcategoria','Id_Categoria_Nueva_Subcategoria');
        $oItem->Id_Categoria_Nueva = $id_categoria;
        $oItem->Id_Subcategoria = $subcategoria;
        $oItem->save();
        unset($oItem);
    }

    return;
}

function resetAsociacionSubcategorias($id_categoria) {
    $query = "DELETE FROM Categoria_Nueva_Subcategoria WHERE Id_Categoria_Nueva = $id_categoria";
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}

?>
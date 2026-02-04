<?php 
    #validaremos que la bodega no se encuentre realizando un inventario

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../class/class.consulta.php');

$id_bodega = isset($_REQUEST['Id_Bodega_Nuevo']) ? $_REQUEST['Id_Bodega_Nuevo'] : false;

if($id_bodega){
    $query = 'SELECT DOC.Id_Doc_Inventario_Fisico
                FROM Doc_Inventario_Fisico DOC 
                INNER JOIN Estiba E ON E.Id_Estiba =  DOC.Id_Estiba 
                WHERE DOC.Estado != "Terminado" AND E.Id_Bodega_Nuevo = '.$id_bodega;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $documentos= $oCon->getData();

    if($documentos){
        $response['type'] = 'error';
        $response['title'] = '¡No se puede realizar la operación!';
        $response['message'] = 'En este momento la bodega que seleccionó se encuentra realizando un inventario.';
    }else{
        $response['type'] = 'success';
        $response['title'] = 'Bodega Disponible';
        $response['message'] = 'Bodega Disponible';
    }

    echo json_encode($response);
}

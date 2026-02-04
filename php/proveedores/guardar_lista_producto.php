<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('./helper_lista_productos/funciones_lista_productos.php');

$cum = isset($_REQUEST['cum']) ? $_REQUEST['cum'] : false;
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$idproveedor = ( isset( $_REQUEST['Id_Proveedor'] ) ? $_REQUEST['Id_Proveedor'] : '' );
$datos = (array) json_decode($datos, true);

$cum = $datos['Cum'];

try {
    if (getProductoByCum($cum, $idproveedor )) {
        throw new Exception('Este cum ya se encuentra registrado');     
    }

    if(validarCumReal($cum)){
        $oItem = new complex('Lista_Precio_Proveedor',"Id_Lista_Precio");

        foreach($datos as $index=>$value){
            $oItem->Id_Proveedor = $idproveedor;
            $oItem->$index        = $value;
        }
            $oItem->save();
            $Id_Lista_Precio = $oItem->getId();
            unset($oItem);
    }

        echo json_encode($Id_Lista_Precio);

} catch (Exception $th) {
    //throw $th;
    header("HTTP/1.0 400 ".$th->getMessage());
    echo json_encode(['message'=>$th->getMessage()]);
}

<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$funcionario_digita = ( isset( $_REQUEST['funcionario_digita'] ) ? $_REQUEST['funcionario_digita'] : '' );
 
$productos = (array) json_decode($productos , true);

try {
    $oItem = new complex("Producto","Id_Producto",$productos['Id_Producto']);
    $oItem->Peso_Presentacion_Maxima=number_format($productos['Peso_Presentacion_Maxima'],2,'.','');
    $oItem->Peso_Presentacion_Minima=number_format($productos['Peso_Presentacion_Minima'],2,'.','');
    $oItem->Peso_Presentacion_Regular=number_format($productos['Peso_Presentacion_Regular'],2,'.','');
    $oItem->Tolerancia=$productos['Torerancia'];
    $oItem->Cantidad_Presentacion=$productos['Cantidad_Presentacion'];
    $oItem->Id_Categoria=$productos['Id_Categoria'];
    $oItem->Embalaje=strtoupper($productos['Embalaje']);
    $oItem->Actualizado='Si';
    $oItem->save();
    unset($oItem);

    $oItem = new complex('Actividad_Producto',"Id_Actividad_Producto");
    $oItem->Id_Producto=$productos['Id_Producto'];
    $oItem->Identificacion_Funcionario=$funcionario_digita;
    $oItem->Detalles="Se  modificaron los siguientes parametros: Peso Pesentacion minima, Peso Presentacion Regular, Peso Presentacion Maxima, Embalaje, cantidad Presentacion del  ".$productos['Nombre'];
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);

    $resultado['mensaje'] = "Se han actualizado correctamente los detalles de los productos.";
    $resultado['tipo'] = "success";
    $resultado['titulo'] = "Operación Exitosa";
} catch (Exception $e) {
    $resultado['mensaje'] = "Ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
    $resultado['tipo'] = "Error";
}

echo json_encode($resultado);

?>

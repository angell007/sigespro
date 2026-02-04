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
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();
$entrega   = ( isset( $_REQUEST['Devolucion'] ) ? $_REQUEST['Devolucion'] : '' );
$productos = ( isset( $_REQUEST['Productos'] ) ? $_REQUEST['Productos'] : '' );

$entrega   = (array) json_decode($entrega);
$productos = (array) json_decode($productos, true);



$oItem = new complex("Dotacion","Id_Dotacion",$entrega["Id_Dotacion"]);
$oItem->Estado="Devuelta";
$oItem->save();
unset($oItem);


$costo      = 0;
$list_prods = '';

foreach($productos as $prod){

    $list_prods .= $prod["Cantidad"].' x '.$prod["Nombre"]." | ";
    if(!$prod["baja"]){
        $query = 'SELECT ID.Id_Inventario_Dotacion
        FROM Inventario_Dotacion ID
        WHERE ID.Nombre LIKE "'.$prod["Nombre"].'"';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $inv = $oCon->getData();
        unset($oCon);
    
        $oItem = new complex("Inventario_Dotacion","Id_Inventario_Dotacion",$inv["Id_Inventario_Dotacion"]);
        $oItem->Cantidad = (FLOAT)$oItem->Cantidad + (FLOAT)$prod["Cantidad"];
        $oItem->save();
        unset($oItem);
    }
    
}
$entrega["Productos"]=trim($list_prods," | ");

$oItem = new complex("Devolucion_Dotacion","Id_Devolucion_Dotacion");
$oItem->Id_Dotacion                 = $entrega["Id_Dotacion"];
$oItem->Fecha                       = date("Y-m-d H:i:s");
$oItem->Identificacion_Funcionario  = $entrega["Identificacion_Funcionario"];
$oItem->Funcionario_Recibe          = $entrega["Entrega"];
$oItem->Detalles                    = $entrega["Detalles"];
$oItem->Productos                   = $entrega["Productos"];
$oItem->save();
unset($oItem);

$resultado["Estado"]="exito";

echo json_encode($resultado);
?>
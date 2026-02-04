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
$entrega   = ( isset( $_REQUEST['Entrega']   ) ? $_REQUEST['Entrega']   : '' );
$productos = ( isset( $_REQUEST['Productos'] ) ? $_REQUEST['Productos'] : '' );

$entrega   = (array) json_decode($entrega);
$productos = (array) json_decode($productos, true);

$costo      = 0;
$list_prods = '';

//////GUARDAR DOTACION

$oItem = new complex("Dotacion","Id_Dotacion");
foreach($entrega as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$Id_Dotacion = $oItem->getId(); 
unset($oItem);

/// GUARDAR PRODUCTOS 
foreach($productos as $prod){
    if($prod["Cantidad_Seleccionada"]!=''&&$prod["Cantidad_Seleccionada"]!="0"){
        $total       = $prod["Cantidad_Seleccionada"]*$prod["Costo"];
        $costo      += $total;
        $total       = '';
        $list_prods .= trim($prod["Cantidad_Seleccionada"]).' x '.trim($prod["Nombre"])." | ";

        ////// INVENTARIO DOTACION
        $oItem = new complex("Inventario_Dotacion","Id_Inventario_Dotacion",$prod["Id_Inventario_Dotacion"]);
        $oItem->Cantidad = (FLOAT)$oItem->Cantidad - (FLOAT)trim($prod["Cantidad_Seleccionada"]);
        $oItem->save();
        $oItem->getId();
        unset($oItem);

        ///// PRODUCTO DOTACION /////

        $proDot = new complex("Producto_Dotacion","Id_Producto_Dotacion");
        $proDot->Cantidad               = $prod["Cantidad_Seleccionada"];
        $proDot->Id_Inventario_Dotacion = $prod["Id_Inventario_Dotacion"];
        $proDot->Costo                  = $prod["Costo"];
        $proDot->id_Dotacion            = $Id_Dotacion;
        $proDot->save();
        unset($proDot);


    }/// FINAL IF
}/// FINAL FOREACH

$entrega["Productos"] = trim($list_prods," | ");
$entrega["Costo"]     = $costo;

$oItem = new complex("Dotacion","Id_Dotacion",$Id_Dotacion);
$oItem->Costo = $costo;
$oItem->save();
unset($oItem);

$resultado["Estado"]="exito";
echo json_encode($resultado);
?>
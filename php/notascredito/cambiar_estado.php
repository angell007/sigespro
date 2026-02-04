<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$contabilizar = new Contabilizar();

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$id = ( isset( $_REQUEST['Id'] ) ? $_REQUEST['Id'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$motivo = ( isset( $_REQUEST['motivo'] ) ? $_REQUEST['motivo'] : '' );

$productos = (array) json_decode($productos , true); 

$id_cliente = 0;

$oItem = new complex('Nota_Credito',"Id_Nota_Credito",$id );
if($tipo=='Aceptar'){
    $oItem->Estado="Aprobada";
    $id_cliente = $oItem->Id_Cliente;
}else if ($tipo=='Rechazar'){
    $oItem->Estado="Rechazada";
    $oItem->Motivo_Rechazo=$motivo;
}
$oItem->save();
$nota = $oItem->getData();
unset($oItem);

if($tipo=='Aceptar'){

/*  
CAMBIOS CARLOS CARDONA 03-09-2020 INGRESA A INVENTARIO EN ACOMODAR
foreach ($productos as $item) {
        $oItem = new complex('Inventario',"Id_inventario",$item['Id_Inventario'] );
        $inv = $oItem->getData();
        $cantidadinventario=number_format($inv["Cantidad"],0,"","");
        $cantidadnota=number_format($item['Cantidad'],0,"","");
        $cantidadfinal= $cantidadinventario+$cantidadnota;        
        $oItem->Cantidad=number_format($cantidadfinal,0,"","");
        $oItem->save();
        unset($oItem);
    } */


    $datos_movimiento_contable = array();

    $datos_movimiento_contable['Id_Registro'] = $id;
    $datos_movimiento_contable['Nit'] = $id_cliente;
    $datos_movimiento_contable['Productos'] = $productos;
    $datos_movimiento_contable['Fecha'] = $nota['Fecha'];

    $contabilizar->CrearMovimientoContable('Nota Credito', $datos_movimiento_contable);
}



$resultado['mensaje'] = "Se ha guardado los cambios de la nota credito con codigo: ". $nota['Codigo'];
$resultado['tipo'] = "success";


echo json_encode($resultado);

?>		